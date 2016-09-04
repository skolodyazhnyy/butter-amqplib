<?php

namespace ButterAMQP;

use ButterAMQP\Exception\AMQPFailure;
use ButterAMQP\Exception\InvalidChannelNumberException;
use ButterAMQP\Framing\Frame;
use ButterAMQP\Framing\Heartbeat;
use ButterAMQP\Framing\Method\ConnectionBlocked;
use ButterAMQP\Framing\Method\ConnectionClose;
use ButterAMQP\Framing\Method\ConnectionCloseOk;
use ButterAMQP\Framing\Method\ConnectionOpen;
use ButterAMQP\Framing\Method\ConnectionOpenOk;
use ButterAMQP\Framing\Method\ConnectionStart;
use ButterAMQP\Framing\Method\ConnectionStartOk;
use ButterAMQP\Framing\Method\ConnectionTune;
use ButterAMQP\Framing\Method\ConnectionTuneOk;
use ButterAMQP\Framing\Method\ConnectionUnblocked;
use ButterAMQP\Heartbeat\TimeHeartbeat;
use ButterAMQP\Security\Authenticator;
use ButterAMQP\Security\AuthenticatorInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

class Connection implements ConnectionInterface, WireSubscriberInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    const STATUS_CLOSED = 0;
    const STATUS_READY = 1;
    const STATUS_BLOCKED = 2;

    /**
     * @var Url
     */
    private $url;

    /**
     * @var WireInterface
     */
    private $wire;

    /**
     * @var AuthenticatorInterface
     */
    private $authenticator;

    /**
     * @var string
     */
    private $status;

    /**
     * @var Channel[]
     */
    private $channels = [];

    /**
     * @var int
     */
    private $frameMax = 0;

    /**
     * @var int
     */
    private $channelMax = 0;

    /**
     * @var int
     */
    private $heartbeat = 0;

    /**
     * @param Url|string             $url
     * @param WireInterface          $wire
     * @param AuthenticatorInterface $authenticator
     */
    public function __construct($url, WireInterface $wire, AuthenticatorInterface $authenticator = null)
    {
        $this->url = $url instanceof Url ? $url : Url::parse($url);
        $this->wire = $wire;
        $this->authenticator = $authenticator ?: Authenticator::build();
        $this->logger = new NullLogger();
    }

    /**
     * @param int $frameMax
     *
     * @return $this
     */
    public function setFrameMax($frameMax)
    {
        $this->frameMax = $frameMax;

        return $this;
    }

    /**
     * @param int $channelMax
     *
     * @return $this
     */
    public function setChannelMax($channelMax)
    {
        $this->channelMax = $channelMax;

        return $this;
    }

    /**
     * @param int $heartbeat
     *
     * @return $this
     */
    public function setHeartbeat($heartbeat)
    {
        $this->heartbeat = $heartbeat;

        return $this;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * {@inheritdoc}
     */
    public function open()
    {
        $this->channels = [];

        $this->wire->open($this->url->getHost(), $this->url->getPort())
            ->subscribe(0, $this);

        $this->wait(ConnectionTune::class);

        $this->logger->debug(sprintf('Opening virtual host "%s"', $this->url->getVhost()));

        $this->send(new ConnectionOpen($this->url->getVhost(), '', false))
            ->wait(ConnectionOpenOk::class);

        $this->status = self::STATUS_READY;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function channel($id = null)
    {
        if ($id === null) {
            $id = count($this->channels) == 0 ? 1 : max(array_keys($this->channels)) + 1;
        }

        if (!is_integer($id) || $id <= 0) {
            throw new InvalidChannelNumberException('Channel ID should be positive integer');
        }

        if (!isset($this->channels[$id])) {
            $channel = new Channel($this->wire, $id);

            if ($channel instanceof LoggerAwareInterface) {
                $channel->setLogger($this->logger);
            }

            $this->channels[$id] = $channel;

            $channel->open();
        }

        return $this->channels[$id];
    }

    /**
     * {@inheritdoc}
     */
    public function close($code = 0, $reason = '')
    {
        $this->send(new ConnectionClose($code, $reason, 0, 0))
            ->wait(ConnectionCloseOk::class);

        $this->status = self::STATUS_CLOSED;

        $this->wire->close();

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function serve($blocking = true, $timeout = null)
    {
        $this->wire->next($blocking, $timeout);

        return $this;
    }

    /**
     * Sends frame to the server.
     *
     * @param Frame $frame
     *
     * @return $this
     */
    private function send(Frame $frame)
    {
        $this->wire->send(0, $frame);

        return $this;
    }

    /**
     * @param string $type
     *
     * @return Frame
     */
    private function wait($type)
    {
        return $this->wire->wait(0, $type);
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch(Frame $frame)
    {
        if ($frame instanceof ConnectionStart) {
            $this->onConnectionStart($frame);
        }

        if ($frame instanceof ConnectionTune) {
            $this->onConnectionTune($frame);
        }

        if ($frame instanceof ConnectionClose) {
            $this->onConnectionClose($frame);
        }

        if ($frame instanceof ConnectionBlocked) {
            $this->onConnectionBlocked($frame);
        }

        if ($frame instanceof ConnectionUnblocked) {
            $this->onConnectionUnblocked($frame);
        }
    }

    /**
     * @param ConnectionStart $frame
     */
    private function onConnectionStart(ConnectionStart $frame)
    {
        $mechanism = $this->authenticator
            ->get(explode(' ', $frame->getMechanisms()));

        list($locale) = explode(' ', $frame->getLocales());

        $this->send(new ConnectionStartOk(
            ['product' => 'ButterAMQP', 'version' => '0.1.0', 'platform' => 'PHP '.PHP_VERSION],
            $mechanism->getName(),
            $mechanism->getResponse($this->url->getUser(), $this->url->getPass()),
            $locale
        ));
    }

    /**
     * @param ConnectionTune $frame
     */
    private function onConnectionTune(ConnectionTune $frame)
    {
        $negotiate = function ($a, $b) {
            return ($a * $b == 0) ? max($a, $b) : min($a, $b);
        };

        $this->channelMax = $negotiate($this->channelMax, $frame->getChannelMax());
        $this->frameMax = $negotiate($this->frameMax, $frame->getFrameMax());
        $this->heartbeat = $negotiate($this->heartbeat, $frame->getHeartbeat());

        $this->logger->debug(sprintf(
            'Tune connection: up to %d channels, %d frame size, heartbeat every %d seconds',
            $this->channelMax,
            $this->frameMax,
            $this->heartbeat
        ));

        $this->send(new ConnectionTuneOk($this->channelMax, $this->frameMax, $this->heartbeat));

        $this->wire->setHeartbeat(new TimeHeartbeat($this->heartbeat))
            ->setFrameMax($this->frameMax);
    }

    /**
     * @param ConnectionClose $frame
     *
     * @throws AMQPFailure
     */
    private function onConnectionClose(ConnectionClose $frame)
    {
        $this->send(new ConnectionCloseOk());
        $this->wire->close();

        $this->status = self::STATUS_CLOSED;

        if ($frame->getReplyCode()) {
            throw AMQPFailure::make($frame->getReplyText(), $frame->getReplyCode());
        }
    }

    /**
     * @param ConnectionBlocked $frame
     */
    private function onConnectionBlocked(ConnectionBlocked $frame)
    {
        $this->status = self::STATUS_BLOCKED;
    }
    /**
     * @param ConnectionUnblocked $frame
     */
    private function onConnectionUnblocked(ConnectionUnblocked $frame)
    {
        $this->status = self::STATUS_READY;
    }
}
