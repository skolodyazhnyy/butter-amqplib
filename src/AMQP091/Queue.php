<?php

namespace ButterAMQP\AMQP091;

use ButterAMQP\AMQP091\Framing\Frame;
use ButterAMQP\AMQP091\Framing\Method\QueueBind;
use ButterAMQP\AMQP091\Framing\Method\QueueBindOk;
use ButterAMQP\AMQP091\Framing\Method\QueueDeclare;
use ButterAMQP\AMQP091\Framing\Method\QueueDeclareOk;
use ButterAMQP\AMQP091\Framing\Method\QueueDelete;
use ButterAMQP\AMQP091\Framing\Method\QueueDeleteOk;
use ButterAMQP\AMQP091\Framing\Method\QueuePurge;
use ButterAMQP\AMQP091\Framing\Method\QueuePurgeOk;
use ButterAMQP\AMQP091\Framing\Method\QueueUnbind;
use ButterAMQP\AMQP091\Framing\Method\QueueUnbindOk;
use ButterAMQP\QueueInterface;
use ButterAMQP\WireInterface;

class Queue implements QueueInterface
{
    const FLAG_NO_WAIT = 0b00000001;
    const FLAG_DURABLE = 0b00000010;
    const FLAG_PASSIVE = 0b00000100;
    const FLAG_EXCLUSIVE = 0b00001000;
    const FLAG_AUTO_DELETE = 0b00010000;
    const FLAG_INTERNAL = 0b00100000;
    const FLAG_IF_UNUSED = 0b01000000;
    const FLAG_IF_EMPTY = 0b10000000;

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
     * @var int|null
     */
    private $messageCount;

    /**
     * @var int|null
     */
    private $consumerCount;

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
    public function define($flags = 0, array $arguments = [])
    {
        $this->send(new QueueDeclare(
            $this->channel,
            0,
            $this->name,
            $flags & self::FLAG_PASSIVE,
            $flags & self::FLAG_DURABLE,
            $flags & self::FLAG_EXCLUSIVE,
            $flags & self::FLAG_AUTO_DELETE,
            $flags & self::FLAG_NO_WAIT,
            $arguments
        ));

        if ($flags & self::FLAG_NO_WAIT) {
            return $this;
        }

        /** @var QueueDeclareOk $frame */
        $frame = $this->wait(QueueDeclareOk::class);

        $this->name = $frame->getQueue();
        $this->messageCount = $frame->getMessageCount();
        $this->consumerCount = $frame->getConsumerCount();

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($flags = 0)
    {
        $this->send(new QueueDelete(
            $this->channel,
            0,
            $this->name,
            $flags & self::FLAG_IF_UNUSED,
            $flags & self::FLAG_IF_EMPTY,
            $flags & self::FLAG_NO_WAIT
        ));

        if ($flags & self::FLAG_NO_WAIT) {
            return $this;
        }

        /** @var QueueDeleteOk $frame */
        $frame = $this->wait(QueueDeleteOk::class);

        $this->messageCount = $frame->getMessageCount();

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function bind($exchange, $routingKey = '', array $arguments = [], $flags = 0)
    {
        $this->send(new QueueBind(
            $this->channel,
            0,
            $this->name,
            (string) $exchange,
            $routingKey,
            $flags & self::FLAG_NO_WAIT,
            $arguments
        ));

        if (!($flags & self::FLAG_NO_WAIT)) {
            $this->wait(QueueBindOk::class);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function unbind($exchange, $routingKey = '', array $arguments = [])
    {
        $this->send(new QueueUnbind(
            $this->channel,
            0,
            $this->name,
            $exchange,
            $routingKey,
            $arguments
        ));

        $this->wait(QueueUnbindOk::class);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function purge($flags = 0)
    {
        $this->send(new QueuePurge(
            $this->channel,
            0,
            $this->name,
            (bool) ($flags & self::FLAG_NO_WAIT)
        ));

        if ($flags & self::FLAG_NO_WAIT) {
            return $this;
        }

        /** @var QueuePurgeOk $frame */
        $frame = $this->wait(QueuePurgeOk::class);

        $this->messageCount = $frame->getMessageCount();

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
     * {@inheritdoc}
     */
    public function messagesCount()
    {
        return $this->messageCount;
    }

    /**
     * {@inheritdoc}
     */
    public function consumerCount()
    {
        return $this->consumerCount;
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
