<?php

namespace ButterAMQP;

use ButterAMQP\Exception\AMQPException;
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
     * Set max frame size.
     * This value could be lowered by server.
     * Zero means - no preference.
     * Minimal value 4096, established by protocol specification.
     *
     * @param int $frameMax maximum desired length of the frame
     *
     * @return $this
     */
    public function setFrameMax($frameMax)
    {
        $this->frameMax = $frameMax;

        return $this;
    }

    /**
     * Set max number of channels in the connection.
     * This value could be lowered by server.
     * Zero means - no preference.
     * Limited to 65535 by protocol specification.
     *
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
     * Set desired heartbeat delay.
     * This value could be lowered by server.
     * Zero means - no heartbeat.
     *
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
     * Connection status. See STATUS_* constants for possible values.
     *
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

        $this->wire->open($this->url)
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
    public function serve($blocking = true)
    {
        $this->wire->next($blocking);

        return $this;
    }

    /**
     * Sends frame to the service channel (#0).
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
     * Wait for a frame in the service channel (#0).
     *
     * @param string|array $type
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
     * This frame is the first frame received from server.
     * It provides server details and requests client credentials.
     *
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
     * This frame is received to setup connection preferences, like max frame size,
     * max number of channel and heartbeat delay.
     *
     * Values in the request can be lowered by client.
     *
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
     * This frame is received once server decide to close connection, normally because an unrecoverable error occur.
     *
     * @param ConnectionClose $frame
     *
     * @throws AMQPException
     */
    private function onConnectionClose(ConnectionClose $frame)
    {
        $this->send(new ConnectionCloseOk());
        $this->wire->close();

        $this->status = self::STATUS_CLOSED;

        if ($frame->getReplyCode()) {
            throw AMQPException::make($frame->getReplyText(), $frame->getReplyCode());
        }
    }

    /**
     * This frame is received once server decide to suspend connection, for example because server
     * run out of memory and can not provide service for the connection. When this happen consumer
     * suppose to suspend all activities until connection.unblocked is received.
     *
     * @param ConnectionBlocked $frame
     */
    private function onConnectionBlocked(ConnectionBlocked $frame)
    {
        $this->status = self::STATUS_BLOCKED;
    }

    /**
     * This frame is received once connection returns back to normal state after being suspended.
     * See onConnectionBlocked above.
     *
     * @param ConnectionUnblocked $frame
     */
    private function onConnectionUnblocked(ConnectionUnblocked $frame)
    {
        $this->status = self::STATUS_READY;
    }
}
