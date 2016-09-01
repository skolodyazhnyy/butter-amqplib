<?php

namespace ButterAMQP;

use ButterAMQP\Framing\Frame;
use ButterAMQP\Framing\Method\QueueBind;
use ButterAMQP\Framing\Method\QueueBindOk;
use ButterAMQP\Framing\Method\QueueDeclare;
use ButterAMQP\Framing\Method\QueueDeclareOk;
use ButterAMQP\Framing\Method\QueueDelete;
use ButterAMQP\Framing\Method\QueueDeleteOk;
use ButterAMQP\Framing\Method\QueuePurge;
use ButterAMQP\Framing\Method\QueuePurgeOk;
use ButterAMQP\Framing\Method\QueueUnbind;
use ButterAMQP\Framing\Method\QueueUnbindOk;

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
        $this->send(new QueuePurge(0, $this->name, (bool) ($flags & self::FLAG_NO_WAIT)));

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
