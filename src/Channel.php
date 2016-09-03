<?php

namespace ButterAMQP;

use ButterAMQP\Exception\UnknownConsumerTagException;
use ButterAMQP\Framing\Content;
use ButterAMQP\Framing\Frame;
use ButterAMQP\Framing\Header;
use ButterAMQP\Framing\Method\BasicAck;
use ButterAMQP\Framing\Method\BasicCancel;
use ButterAMQP\Framing\Method\BasicCancelOk;
use ButterAMQP\Framing\Method\BasicConsume;
use ButterAMQP\Framing\Method\BasicConsumeOk;
use ButterAMQP\Framing\Method\BasicDeliver;
use ButterAMQP\Framing\Method\BasicNack;
use ButterAMQP\Framing\Method\BasicPublish;
use ButterAMQP\Framing\Method\BasicQos;
use ButterAMQP\Framing\Method\BasicQosOk;
use ButterAMQP\Framing\Method\BasicReject;
use ButterAMQP\Framing\Method\ChannelClose;
use ButterAMQP\Framing\Method\ChannelCloseOk;
use ButterAMQP\Framing\Method\ChannelFlow;
use ButterAMQP\Framing\Method\ChannelFlowOk;
use ButterAMQP\Framing\Method\ChannelOpen;
use ButterAMQP\Framing\Method\ChannelOpenOk;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

class Channel implements ChannelInterface, WireSubscriberInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    const STATUS_CLOSED = 0;
    const STATUS_READY = 1;
    const STATUS_INACTIVE = 2;

    /**
     * @var int
     */
    private $id;

    /**
     * @var WireInterface
     */
    private $wire;

    /**
     * @var string
     */
    private $status = self::STATUS_CLOSED;

    /**
     * @var callable[]
     */
    private $consumers = [];

    /**
     * @param WireInterface $wire
     * @param int           $id
     */
    public function __construct(WireInterface $wire, $id)
    {
        $this->id = $id;
        $this->wire = $wire;
        $this->logger = new NullLogger();
    }

    /**
     * {@inheritdoc}
     */
    public function open()
    {
        if ($this->status != self::STATUS_CLOSED) {
            return $this;
        }

        $this->wire->subscribe($this->id, $this);

        $this->send(new ChannelOpen(''))
            ->wait(ChannelOpenOk::class);

        $this->status = self::STATUS_READY;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function flow($active)
    {
        /** @var ChannelFlowOk $frame */
        $frame = $this->send(new ChannelFlow($active))
            ->wait(ChannelFlowOk::class);

        $this->status = $frame->isActive() ? self::STATUS_READY :
            self::STATUS_INACTIVE;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        $this->send(new ChannelClose(0, '', 0, 0))
            ->wait(ChannelCloseOk::class);

        $this->status = self::STATUS_CLOSED;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function qos($prefetchSize, $prefetchCount, $globally = false)
    {
        $this->send(new BasicQos($prefetchSize, $prefetchCount, $globally))
            ->wait(BasicQosOk::class);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function exchange($name)
    {
        return new Exchange($this->wire, $this->id, $name);
    }

    /**
     * {@inheritdoc}
     */
    public function queue($name = '')
    {
        return new Queue($this->wire, $this->id, $name);
    }

    /**
     * {@inheritdoc}
     */
    public function consume($queue, callable $callback, $flags = 0, $tag = '', array $arguments = [])
    {
        if (empty($tag) && $flags & Consumer::FLAG_NO_WAIT) {
            $tag = uniqid('php-consumer-');
        }

        $this->send(new BasicConsume(
            0,
            $queue,
            $tag,
            $flags & Consumer::FLAG_NO_LOCAL,
            $flags & Consumer::FLAG_NO_ACK,
            $flags & Consumer::FLAG_EXCLUSIVE,
            $flags & Consumer::FLAG_NO_WAIT,
            $arguments
        ));

        if (!($flags & Consumer::FLAG_NO_WAIT)) {
            $tag = $this->wait(BasicConsumeOk::class)
                ->getConsumerTag();
        }

        $this->consumers[$tag] = $callback;

        return new Consumer($this, $tag);
    }

    /**
     * {@inheritdoc}
     */
    public function cancel($tag, $flags = 0)
    {
        $this->send(new BasicCancel($tag, $flags & Consumer::FLAG_NO_WAIT));

        unset($this->consumers[$tag]);

        if ($flags & Consumer::FLAG_NO_WAIT) {
            return $this;
        }

        $this->wait(BasicCancelOk::class);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function publish(Message $message, $exchange = '', $routingKey = '', $flags = 0)
    {
        $this->send(new BasicPublish(
            0,
            $exchange,
            $routingKey,
            (bool) ($flags & Message::FLAG_MANDATORY),
            (bool) ($flags & Message::FLAG_IMMEDIATE)
        ));

        $body = $message->getBody();

        $this->send(new Header(60, 0, Binary::length($body), $message->getProperties()));
        $this->send(new Content($body));

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function ack($deliveryTag, $multiple = false)
    {
        $this->send(new BasicAck($deliveryTag, $multiple));

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function reject($deliveryTag, $requeue = true, $multiple = false)
    {
        $multiple ? $this->send(new BasicNack($deliveryTag, $multiple, $requeue)) :
            $this->send(new BasicReject($deliveryTag, $requeue));

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function hasConsumer($tag)
    {
        return isset($this->consumers[$tag]);
    }

    /**
     * {@inheritdoc}
     */
    public function getConsumerTags()
    {
        return array_keys($this->consumers);
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
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
        $this->wire->send($this->id, $frame);

        return $this;
    }

    /**
     * @param string $type
     *
     * @return Frame
     */
    private function wait($type)
    {
        return $this->wire->wait($this->id, $type);
    }

    /**
     * @param Frame $frame
     */
    public function dispatch(Frame $frame)
    {
        if ($frame instanceof ChannelClose) {
            $this->onChannelClose($frame);
        }

        if ($frame instanceof ChannelFlow) {
            $this->onChannelFlow($frame);
        }

        if ($frame instanceof BasicDeliver) {
            $this->onBasicDeliver($frame);
        }
    }

    /**
     * @param BasicDeliver $frame
     *
     * @throws \Exception
     */
    private function onBasicDeliver(BasicDeliver $frame)
    {
        /** @var Header $header */
        $header = $this->wait(Header::class);
        $content = '';

        while ($header->getSize() > Binary::length($content)) {
            $content .= $this->wait(Content::class)->getData();
        }

        if (!isset($this->consumers[$frame->getConsumerTag()])) {
            throw new UnknownConsumerTagException(sprintf(
                'Consumer with tag "%s" does not exist',
                $frame->getConsumerTag()
            ));
        }

        $delivery = new Delivery(
            $this,
            $frame->getConsumerTag(),
            $frame->getDeliveryTag(),
            $frame->isRedelivered(),
            $frame->getExchange(),
            $frame->getRoutingKey(),
            $content,
            $header->getProperties()
        );

        call_user_func($this->consumers[$frame->getConsumerTag()], $delivery);
    }

    /**
     * @param ChannelFlow $frame
     */
    private function onChannelFlow(ChannelFlow $frame)
    {
        $this->send(new ChannelFlowOk($frame->isActive()));

        $this->status = $frame->isActive() ? self::STATUS_READY : self::STATUS_INACTIVE;
    }

    /**
     * @param ChannelClose $frame
     */
    private function onChannelClose(ChannelClose $frame)
    {
        $this->send(new ChannelCloseOk());

        $this->status = self::STATUS_CLOSED;
    }
}
