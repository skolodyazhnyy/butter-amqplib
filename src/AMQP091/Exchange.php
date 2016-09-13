<?php

namespace ButterAMQP\AMQP091;

use ButterAMQP\ExchangeInterface;
use ButterAMQP\AMQP091\Framing\Frame;
use ButterAMQP\AMQP091\Framing\Method\ExchangeBind;
use ButterAMQP\AMQP091\Framing\Method\ExchangeBindOk;
use ButterAMQP\AMQP091\Framing\Method\ExchangeDeclare;
use ButterAMQP\AMQP091\Framing\Method\ExchangeDeclareOk;
use ButterAMQP\AMQP091\Framing\Method\ExchangeDelete;
use ButterAMQP\AMQP091\Framing\Method\ExchangeDeleteOk;
use ButterAMQP\AMQP091\Framing\Method\ExchangeUnbind;
use ButterAMQP\AMQP091\Framing\Method\ExchangeUnbindOk;

class Exchange implements ExchangeInterface
{
    /**
     * @var WireInterface
     */
    private $wire;

    /**
     * @var int
     */
    private $channel;

    /**
     * @var string
     */
    private $name;

    /**
     * @param WireInterface $wire
     * @param int           $channel
     * @param string        $name
     */
    public function __construct(WireInterface $wire, $channel, $name)
    {
        $this->wire = $wire;
        $this->channel = $channel;
        $this->name = $name;
    }

    /**
     * {@inheritdoc}
     */
    public function define($type, $flags = 0, array $arguments = [])
    {
        $this->send(new ExchangeDeclare(
            $this->channel,
            0,
            $this->name,
            $type,
            (bool) ($flags & self::FLAG_PASSIVE),
            (bool) ($flags & self::FLAG_DURABLE),
            (bool) ($flags & self::FLAG_AUTO_DELETE),
            (bool) ($flags & self::FLAG_INTERNAL),
            (bool) ($flags & self::FLAG_NO_WAIT),
            $arguments
        ));

        if (!($flags & self::FLAG_NO_WAIT)) {
            $this->wait(ExchangeDeclareOk::class);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($flags = 0)
    {
        $this->send(new ExchangeDelete(
            $this->channel,
            0,
            $this->name,
            (bool) ($flags & self::FLAG_IF_UNUSED),
            (bool) ($flags & self::FLAG_NO_WAIT)
        ));

        if (!($flags & self::FLAG_NO_WAIT)) {
            $this->wait(ExchangeDeleteOk::class);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function bind($exchange, $routingKey = '', array $arguments = [], $flags = 0)
    {
        $this->send(new ExchangeBind(
            $this->channel,
            0,
            $exchange,
            $this->name,
            $routingKey,
            (bool) ($flags & self::FLAG_NO_WAIT),
            $arguments
        ));

        if (!($flags & self::FLAG_NO_WAIT)) {
            $this->wait(ExchangeBindOk::class);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function unbind($exchange, $routingKey = '', array $arguments = [], $flags = 0)
    {
        $this->send(new ExchangeUnbind(
            $this->channel,
            0,
            $exchange,
            $this->name,
            $routingKey,
            (bool) ($flags & self::FLAG_NO_WAIT),
            $arguments
        ));

        if (!($flags & self::FLAG_NO_WAIT)) {
            $this->wait(ExchangeUnbindOk::class);
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

    /**
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
     * @param string|array $type
     *
     * @return Frame
     */
    private function wait($type)
    {
        return $this->wire->wait($this->channel, $type);
    }
}
