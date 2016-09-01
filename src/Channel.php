<?php

namespace ButterAMQP;

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
     * @param int           $id
     * @param WireInterface $wire
     */
    public function __construct($id, WireInterface $wire)
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
     * @param string   $queue
     * @param callable $callback
     * @param int      $flags
     * @param string   $tag
     * @param array    $arguments
     *
     * @return ConsumerInterface
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
            $flags & Message::FLAG_MANDATORY,
            $flags & Message::FLAG_IMMEDIATE
        ));

        $body = $message->getBody();

        $this->send(new Header(60, 0, Binary::length($body), $message->getProperties()));
        $this->send(new Content($body));

        return $this;
    }

    /**
     * @param string $deliveryTag
     * @param bool   $multiple
     *
     * @return $this
     */
    public function ack($deliveryTag, $multiple = false)
    {
        $this->send(new BasicAck($deliveryTag, $multiple));

        return $this;
    }

    /**
     * @param string $deliveryTag
     * @param bool   $requeue
     * @param bool   $multiple
     *
     * @return $this
     */
    public function reject($deliveryTag, $requeue = true, $multiple = false)
    {
        $multiple ? $this->send(new BasicNack($deliveryTag, $multiple, $requeue)) :
            $this->send(new BasicReject($deliveryTag, $requeue));

        return $this;
    }

    /**
     * @param string $tag
     *
     * @return bool
     */
    public function hasConsumer($tag)
    {
        return isset($this->consumers[$tag]);
    }

    public function getConsumerTags()
    {
        // TODO: Implement getConsumerTags() method.
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
        if ($frame instanceof ChannelFlow) {
            $this->onChannelFlow($frame);
        }

        if ($frame instanceof BasicDeliver) {
            $this->onBasicDeliver($frame);
        }
    }

    /**
     * @param BasicDeliver $frame
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
            // @todo: reject?! fail!?
            // @todo: can we skip reading and buffering content and header if there is no consumer?
            return;
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
        $this->status = $frame->isActive() ? self::STATUS_READY : self::STATUS_INACTIVE;
    }
}
