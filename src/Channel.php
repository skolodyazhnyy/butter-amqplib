<?php

namespace AMQLib;

use AMQLib\Framing\Content;
use AMQLib\Framing\Frame;
use AMQLib\Framing\Header;
use AMQLib\Framing\Method\BasicAck;
use AMQLib\Framing\Method\BasicCancel;
use AMQLib\Framing\Method\BasicCancelOk;
use AMQLib\Framing\Method\BasicConsume;
use AMQLib\Framing\Method\BasicConsumeOk;
use AMQLib\Framing\Method\BasicDeliver;
use AMQLib\Framing\Method\BasicNack;
use AMQLib\Framing\Method\BasicPublish;
use AMQLib\Framing\Method\BasicQos;
use AMQLib\Framing\Method\BasicQosOk;
use AMQLib\Framing\Method\BasicReject;
use AMQLib\Framing\Method\ChannelClose;
use AMQLib\Framing\Method\ChannelCloseOk;
use AMQLib\Framing\Method\ChannelFlow;
use AMQLib\Framing\Method\ChannelFlowOk;
use AMQLib\Framing\Method\ChannelOpen;
use AMQLib\Framing\Method\ChannelOpenOk;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

class Channel implements ChannelInterface, FrameChannelInterface, FrameHandlerInterface, LoggerAwareInterface
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
     * @var FrameConnectionInterface
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
     * @param int                      $id
     * @param FrameConnectionInterface $wire
     */
    public function __construct($id, FrameConnectionInterface $wire)
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

        $this->send(new ChannelOpen(''))
            ->wait(ChannelOpenOk::class);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function flow($active)
    {
        $this->send(new ChannelFlow($active))
            ->wait(ChannelFlowOk::class);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        $this->send(new ChannelClose(0, '', 0, 0))
            ->wait(ChannelCloseOk::class);

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
        return new Exchange($this, $name);
    }

    /**
     * {@inheritdoc}
     */
    public function queue($name = '')
    {
        return new Queue($this, $name);
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
     * Sends frame to the server.
     *
     * @param Frame $frame
     *
     * @return $this
     */
    public function send(Frame $frame)
    {
        $this->wire->send($this->id, $frame);

        return $this;
    }

    /**
     * @param string $type
     *
     * @return Frame
     */
    public function wait($type)
    {
        return $this->wire->wait($this->id, $type);
    }

    /**
     * @param Frame $frame
     */
    public function handle(Frame $frame)
    {
        if ($frame instanceof ChannelFlow) {
            $this->status = $frame->isActive() ? self::STATUS_READY : self::STATUS_INACTIVE;
        }

        if ($frame instanceof ChannelFlowOk) {
            $this->status = $frame->isActive() ? self::STATUS_READY : self::STATUS_INACTIVE;
        }

        if ($frame instanceof ChannelCloseOk) {
            $this->status = self::STATUS_CLOSED;
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
}
