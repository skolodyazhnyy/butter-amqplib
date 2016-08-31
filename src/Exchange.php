<?php

namespace AMQLib;

use AMQLib\Framing\Method\ExchangeBind;
use AMQLib\Framing\Method\ExchangeBindOk;
use AMQLib\Framing\Method\ExchangeDeclare;
use AMQLib\Framing\Method\ExchangeDeclareOk;
use AMQLib\Framing\Method\ExchangeDelete;
use AMQLib\Framing\Method\ExchangeDeleteOk;
use AMQLib\Framing\Method\ExchangeUnbind;
use AMQLib\Framing\Method\ExchangeUnbindOk;

class Exchange implements ExchangeInterface
{
    const FLAG_NO_WAIT = 0b00000001;
    const FLAG_DURABLE = 0b00000010;
    const FLAG_PASSIVE = 0b00000100;
    const FLAG_AUTO_DELETE = 0b00001000;
    const FLAG_INTERNAL = 0b00010000;
    const FLAG_IF_UNUSED = 0b00100000;

    const TYPE_TOPIC = 'topic';
    const TYPE_DIRECT = 'direct';
    const TYPE_FANOUT = 'fanout';
    const TYPE_HEADERS = 'headers';

    /**
     * @var Channel
     */
    private $channel;

    /**
     * @var string
     */
    private $name;

    /**
     * @param Channel $channel
     * @param string  $name
     */
    public function __construct(Channel $channel, $name)
    {
        $this->channel = $channel;
        $this->name = $name;
    }

    /**
     * {@inheritdoc}
     */
    public function define($type, $flags = 0, array $arguments = [])
    {
        $this->channel->send(new ExchangeDeclare(
            0,
            $this->name,
            $type,
            $flags & self::FLAG_PASSIVE,
            $flags & self::FLAG_DURABLE,
            $flags & self::FLAG_AUTO_DELETE,
            $flags & self::FLAG_INTERNAL,
            $flags & self::FLAG_NO_WAIT,
            $arguments
        ));

        if (!($flags & self::FLAG_NO_WAIT)) {
            $this->channel->wait(ExchangeDeclareOk::class);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($flags = 0)
    {
        $this->channel->send(new ExchangeDelete(
            0,
            $this->name,
            $flags & self::FLAG_IF_UNUSED,
            $flags & self::FLAG_NO_WAIT
        ));

        if (!($flags & self::FLAG_NO_WAIT)) {
            $this->channel->wait(ExchangeDeleteOk::class);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function publish(Message $message, $routingKey = '', $flags = 0)
    {
        return $this->channel->publish($message, $this->name, $routingKey, $flags);
    }

    /**
     * {@inheritdoc}
     */
    public function bind($exchange, $routingKey = '', array $arguments = [], $flags = 0)
    {
        $this->channel->send(new ExchangeBind(
            0,
            $exchange,
            $this->name,
            $routingKey,
            $flags & self::FLAG_NO_WAIT,
            $arguments
        ));

        if (!($flags & self::FLAG_NO_WAIT)) {
            $this->channel->wait(ExchangeBindOk::class);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function unbind($exchange, $routingKey = '', array $arguments = [], $flags = 0)
    {
        $this->channel->send(new ExchangeUnbind(
            0,
            $exchange,
            $this->name,
            $routingKey,
            $flags & self::FLAG_NO_WAIT,
            $arguments
        ));

        if (!($flags & self::FLAG_NO_WAIT)) {
            $this->channel->wait(ExchangeUnbindOk::class);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function name()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->name;
    }
}
