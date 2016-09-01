<?php

namespace ButterAMQP;

use ButterAMQP\Framing\Frame;
use ButterAMQP\Framing\Method\ExchangeBind;
use ButterAMQP\Framing\Method\ExchangeBindOk;
use ButterAMQP\Framing\Method\ExchangeDeclare;
use ButterAMQP\Framing\Method\ExchangeDeclareOk;
use ButterAMQP\Framing\Method\ExchangeDelete;
use ButterAMQP\Framing\Method\ExchangeDeleteOk;
use ButterAMQP\Framing\Method\ExchangeUnbind;
use ButterAMQP\Framing\Method\ExchangeUnbindOk;

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
        $this->wire->send($this->channel, $frame);

        return $this;
    }

    /**
     * @param string $type
     *
     * @return Frame
     */
    private function wait($type)
    {
        return $this->wire->wait($this->channel, $type);
    }
}
