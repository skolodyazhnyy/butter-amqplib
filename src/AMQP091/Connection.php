<?php

namespace ButterAMQP\AMQP091;

use ButterAMQP\ConnectionInterface;
use ButterAMQP\Exception\AMQPException;
use ButterAMQP\Exception\InvalidChannelNumberException;
use ButterAMQP\AMQP091\Framing\Frame;
use ButterAMQP\AMQP091\Framing\Heartbeat;
use ButterAMQP\AMQP091\Framing\Method\ConnectionBlocked;
use ButterAMQP\AMQP091\Framing\Method\ConnectionClose;
use ButterAMQP\AMQP091\Framing\Method\ConnectionCloseOk;
use ButterAMQP\AMQP091\Framing\Method\ConnectionOpen;
use ButterAMQP\AMQP091\Framing\Method\ConnectionOpenOk;
use ButterAMQP\AMQP091\Framing\Method\ConnectionStart;
use ButterAMQP\AMQP091\Framing\Method\ConnectionStartOk;
use ButterAMQP\AMQP091\Framing\Method\ConnectionTune;
use ButterAMQP\AMQP091\Framing\Method\ConnectionTuneOk;
use ButterAMQP\AMQP091\Framing\Method\ConnectionUnblocked;
use ButterAMQP\Heartbeat\TimeHeartbeat;
use ButterAMQP\Security\Authenticator;
use ButterAMQP\Security\AuthenticatorInterface;
use ButterAMQP\Url;

class Connection implements ConnectionInterface, WireSubscriberInterface
{
    const STATUS_CLOSED = 'closed';
    const STATUS_READY = 'ready';
    const STATUS_BLOCKED = 'blocked';

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
     * @var array
     */
    private $capabilities = [];

    /**
     * @param Url|string             $url
     * @param WireInterface          $wire
     * @param AuthenticatorInterface $authenticator
     */
    public function __construct(Url $url, WireInterface $wire, AuthenticatorInterface $authenticator = null)
    {
        $this->url = $url;
        $this->wire = $wire;
        $this->authenticator = $authenticator ?: Authenticator::build();
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
        $this->capabilities = [];

        $this->wire->open($this->url)
            ->subscribe(0, $this);

        $this->wait(ConnectionTune::class);

        $this->send(new ConnectionOpen(0, $this->url->getVhost(), '', false))
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
            $id = $this->allocateChannelNumber();
        }

        if (!$this->isChannelNumberValid($id)) {
            throw new InvalidChannelNumberException('Channel ID should be positive integer');
        }

        if (!isset($this->channels[$id])) {
            $this->channels[$id] = $this->openChannel($id);
        }

        return $this->channels[$id];
    }

    /**
     * @return int
     */
    private function allocateChannelNumber()
    {
        return count($this->channels) == 0 ? 1 : max(array_keys($this->channels)) + 1;
    }

    /**
     * @param int $id
     *
     * @return bool
     */
    private function isChannelNumberValid($id)
    {
        return is_integer($id) && $id > 0;
    }

    /**
     * @param int $id
     *
     * @return Channel
     */
    private function openChannel($id)
    {
        $channel = new Channel($this->wire, $id);
        $channel->open();

        return $channel;
    }

    /**
     * {@inheritdoc}
     */
    public function close($code = 0, $reason = '')
    {
        $this->send(new ConnectionClose(0, $code, $reason, 0, 0))
            ->wait(ConnectionCloseOk::class);

        $this->status = self::STATUS_CLOSED;

        $this->wire->close();

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function isSupported($capability)
    {
        return isset($this->capabilities[$capability]) ?
            (bool) $this->capabilities[$capability] : false;
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
        $this->wire->send($frame);

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
        } elseif ($frame instanceof ConnectionTune) {
            $this->onConnectionTune($frame);
        } elseif ($frame instanceof ConnectionClose) {
            $this->onConnectionClose($frame);
        } elseif ($frame instanceof ConnectionBlocked) {
            $this->onConnectionBlocked($frame);
        } elseif ($frame instanceof ConnectionUnblocked) {
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
        $properties = $frame->getServerProperties();

        $this->capabilities = isset($properties['capabilities']) ?
            $properties['capabilities'] : [];

        $mechanism = $this->authenticator
            ->get(explode(' ', $frame->getMechanisms()));

        list($locale) = explode(' ', $frame->getLocales());

        $this->send(new ConnectionStartOk(
            0,
            $this->getClientProperties(),
            $mechanism->getName(),
            $mechanism->getResponse($this->url->getUser(), $this->url->getPassword()),
            $locale
        ));
    }

    /**
     * @return array
     */
    private function getClientProperties()
    {
        return [
            'platform' => sprintf('PHP %d.%d', PHP_MAJOR_VERSION, PHP_MINOR_VERSION),
            'product' => 'ButterAMQP',
            'version' => '0.1.0',
            'capabilities' => [
                'publisher_confirms' => true,
                'exchange_exchange_bindings' => true,
                'basic.nack' => true,
                'connection.blocked' => true,
                'consumer_cancel_notify' => true,
                'authentication_failure_close' => true,
            ],
        ];
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

        $channelMax = $negotiate($this->url->getQueryParameter('channel_max', 0), $frame->getChannelMax());
        $frameMax = $negotiate($this->url->getQueryParameter('frame_max', 0), $frame->getFrameMax());
        $heartbeat = $negotiate($this->url->getQueryParameter('heartbeat', 60), $frame->getHeartbeat());

        $this->send(new ConnectionTuneOk(0, $channelMax, $frameMax, $heartbeat));

        $this->wire->setHeartbeat(new TimeHeartbeat($heartbeat))
            ->setFrameMax($frameMax);
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
        $this->send(new ConnectionCloseOk(0));
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
