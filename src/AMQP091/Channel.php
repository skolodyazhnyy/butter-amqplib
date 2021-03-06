<?php

namespace ButterAMQP\AMQP091;

use ButterAMQP\ChannelInterface;
use ButterAMQP\Confirm;
use ButterAMQP\Delivery;
use ButterAMQP\Exception\AMQPException;
use ButterAMQP\Exception\NoReturnException;
use ButterAMQP\Exception\TransactionNotSelectedException;
use ButterAMQP\Exception\UnknownConsumerTagException;
use ButterAMQP\AMQP091\Framing\Content;
use ButterAMQP\AMQP091\Framing\Frame;
use ButterAMQP\AMQP091\Framing\Header;
use ButterAMQP\AMQP091\Framing\Method\BasicAck;
use ButterAMQP\AMQP091\Framing\Method\BasicCancel;
use ButterAMQP\AMQP091\Framing\Method\BasicCancelOk;
use ButterAMQP\AMQP091\Framing\Method\BasicConsume;
use ButterAMQP\AMQP091\Framing\Method\BasicConsumeOk;
use ButterAMQP\AMQP091\Framing\Method\BasicDeliver;
use ButterAMQP\AMQP091\Framing\Method\BasicGet;
use ButterAMQP\AMQP091\Framing\Method\BasicGetEmpty;
use ButterAMQP\AMQP091\Framing\Method\BasicGetOk;
use ButterAMQP\AMQP091\Framing\Method\BasicNack;
use ButterAMQP\AMQP091\Framing\Method\BasicPublish;
use ButterAMQP\AMQP091\Framing\Method\BasicQos;
use ButterAMQP\AMQP091\Framing\Method\BasicQosOk;
use ButterAMQP\AMQP091\Framing\Method\BasicRecover;
use ButterAMQP\AMQP091\Framing\Method\BasicRecoverOk;
use ButterAMQP\AMQP091\Framing\Method\BasicReject;
use ButterAMQP\AMQP091\Framing\Method\BasicReturn;
use ButterAMQP\AMQP091\Framing\Method\ChannelClose;
use ButterAMQP\AMQP091\Framing\Method\ChannelCloseOk;
use ButterAMQP\AMQP091\Framing\Method\ChannelFlow;
use ButterAMQP\AMQP091\Framing\Method\ChannelFlowOk;
use ButterAMQP\AMQP091\Framing\Method\ChannelOpen;
use ButterAMQP\AMQP091\Framing\Method\ChannelOpenOk;
use ButterAMQP\AMQP091\Framing\Method\ConfirmSelect;
use ButterAMQP\AMQP091\Framing\Method\ConfirmSelectOk;
use ButterAMQP\AMQP091\Framing\Method\TxCommit;
use ButterAMQP\AMQP091\Framing\Method\TxCommitOk;
use ButterAMQP\AMQP091\Framing\Method\TxRollback;
use ButterAMQP\AMQP091\Framing\Method\TxRollbackOk;
use ButterAMQP\AMQP091\Framing\Method\TxSelect;
use ButterAMQP\AMQP091\Framing\Method\TxSelectOk;
use ButterAMQP\Message;
use ButterAMQP\Returned;

class Channel implements ChannelInterface, WireSubscriberInterface
{
    const STATUS_CLOSED = 0;
    const STATUS_READY = 1;
    const STATUS_INACTIVE = 2;

    const MODE_NORMAL = 0;
    const MODE_CONFIRM = 1;
    const MODE_TX = 2;

    /**
     * @var int
     */
    private $id;

    /**
     * @var WireInterface
     */
    private $wire;

    /**
     * @var int
     */
    private $status = self::STATUS_CLOSED;

    /**
     * @var int
     */
    private $mode = self::MODE_NORMAL;

    /**
     * @var callable[]
     */
    private $consumers = [];

    /**
     * @var callable
     */
    private $returnCallable;

    /**
     * @var callable
     */
    private $confirmCallable;

    /**
     * @param WireInterface $wire
     * @param int           $id
     */
    public function __construct(WireInterface $wire, $id)
    {
        $this->id = $id;
        $this->wire = $wire;
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
        $this->wire->send(new ChannelOpen($this->id, ''));
        $this->wire->wait($this->id, ChannelOpenOk::class);

        $this->status = self::STATUS_READY;
        $this->mode = self::MODE_NORMAL;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function flow($active)
    {
        $this->wire->send(new ChannelFlow($this->id, $active));

        /** @var ChannelFlowOk $frame */
        $frame = $this->wire->wait($this->id, ChannelFlowOk::class);

        $this->status = $frame->isActive() ? self::STATUS_READY :
            self::STATUS_INACTIVE;

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
     * {@inheritdoc}
     */
    public function close()
    {
        $this->wire->send(new ChannelClose($this->id, 0, '', 0, 0));
        $this->wire->wait($this->id, ChannelCloseOk::class);

        $this->status = self::STATUS_CLOSED;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function qos($prefetchSize, $prefetchCount, $globally = false)
    {
        $this->wire->send(new BasicQos($this->id, $prefetchSize, $prefetchCount, $globally));
        $this->wire->wait($this->id, BasicQosOk::class);

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

        $this->wire->send(new BasicConsume(
            $this->id,
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
            $tag = $this->wire->wait($this->id, BasicConsumeOk::class)
                ->getConsumerTag();
        }

        $this->consumers[$tag] = $callback;

        return new Consumer($this, $tag);
    }

    /**
     * {@inheritdoc}
     */
    public function get($queue, $withAck = true)
    {
        /* @var BasicGetOk|BasicGetEmpty $frame */
        $this->wire->send(new BasicGet($this->id, 0, $queue, !$withAck));

        $frame = $this->wire->wait($this->id, [BasicGetOk::class, BasicGetEmpty::class]);

        if ($frame instanceof BasicGetEmpty) {
            return null;
        }

        $header = $this->readHeader();
        $content = $this->readContent($header->getSize());

        return new Delivery(
            $this,
            '',
            $frame->getDeliveryTag(),
            $frame->isRedelivered(),
            $frame->getExchange(),
            $frame->getRoutingKey(),
            $content,
            $header->getProperties()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function recover($requeue = true)
    {
        $this->wire->send(new BasicRecover($this->id, $requeue));
        $this->wire->wait($this->id, BasicRecoverOk::class);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function cancel($tag, $flags = 0)
    {
        $this->wire->send(new BasicCancel($this->id, $tag, $flags & Consumer::FLAG_NO_WAIT));

        unset($this->consumers[$tag]);

        if ($flags & Consumer::FLAG_NO_WAIT) {
            return $this;
        }

        $this->wire->wait($this->id, BasicCancelOk::class);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function publish(Message $message, $exchange = '', $routingKey = '', $flags = 0)
    {
        $this->wire->send(new BasicPublish(
            $this->id,
            0,
            $exchange,
            $routingKey,
            (bool) ($flags & Message::FLAG_MANDATORY),
            (bool) ($flags & Message::FLAG_IMMEDIATE)
        ));

        $body = $message->getBody();

        $this->wire->send(new Header($this->id, 60, 0, strlen($body), $message->getProperties()));
        $this->wire->send(new Content($this->id, $body));

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function ack($deliveryTag, $multiple = false)
    {
        $this->wire->send(new BasicAck($this->id, $deliveryTag, $multiple));

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function reject($deliveryTag, $requeue = true, $multiple = false)
    {
        $multiple ? $this->wire->send(new BasicNack($this->id, $deliveryTag, $multiple, $requeue)) :
            $this->wire->send(new BasicReject($this->id, $deliveryTag, $requeue));

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function onReturn(callable $callable)
    {
        $this->returnCallable = $callable;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function selectConfirm(callable $callable, $noWait = false)
    {
        $this->confirmCallable = $callable;

        $this->wire->send(new ConfirmSelect($this->id, $noWait));

        if (!$noWait) {
            $this->wire->wait($this->id, ConfirmSelectOk::class);
        }

        $this->mode = self::MODE_CONFIRM;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function selectTx()
    {
        $this->wire->send(new TxSelect($this->id));
        $this->wire->wait($this->id, TxSelectOk::class);

        $this->mode = self::MODE_TX;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function txCommit()
    {
        if ($this->mode != self::MODE_TX) {
            throw new TransactionNotSelectedException('Channel is not in transaction mode. Use Channel::selectTx() to select transaction mode on this channel.');
        }

        $this->wire->send(new TxCommit($this->id));
        $this->wire->wait($this->id, TxCommitOk::class);

        return;
    }

    /**
     * {@inheritdoc}
     */
    public function txRollback()
    {
        if ($this->mode != self::MODE_TX) {
            throw new TransactionNotSelectedException('Channel is not in transaction mode. Use Channel::selectTx() to select transaction mode on this channel.');
        }

        $this->wire->send(new TxRollback($this->id));
        $this->wire->wait($this->id, TxRollbackOk::class);

        return;
    }

    /**
     * {@inheritdoc}
     */
    public function hasConsumer($tag)
    {
        return isset($this->consumers[(string) $tag]);
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
        $this->wire->send($frame);

        return $this;
    }

    /**
     * @param Frame $frame
     */
    public function dispatch(Frame $frame)
    {
        if ($frame instanceof ChannelClose) {
            $this->onChannelClose($frame);
        } elseif ($frame instanceof ChannelFlow) {
            $this->onChannelFlow($frame);
        } elseif ($frame instanceof BasicDeliver) {
            $this->onBasicDeliver($frame);
        } elseif ($frame instanceof BasicReturn) {
            $this->onBasicReturn($frame);
        } elseif ($frame instanceof BasicAck) {
            $this->onBasicAck($frame);
        } elseif ($frame instanceof BasicNack) {
            $this->onBasicNack($frame);
        } elseif ($frame instanceof BasicCancel) {
            $this->onBasicCancel($frame);
        }
    }

    /**
     * @param BasicDeliver $frame
     *
     * @throws \Exception
     */
    private function onBasicDeliver(BasicDeliver $frame)
    {
        $header = $this->readHeader();
        $content = $this->readContent($header->getSize());

        if (!isset($this->consumers[$frame->getConsumerTag()])) {
            throw new UnknownConsumerTagException(sprintf(
                'Consumer with tag "%s" does not exist',
                $frame->getConsumerTag()
            ));
        }

        call_user_func($this->consumers[$frame->getConsumerTag()], new Delivery(
            $this,
            $frame->getConsumerTag(),
            $frame->getDeliveryTag(),
            $frame->isRedelivered(),
            $frame->getExchange(),
            $frame->getRoutingKey(),
            $content,
            $header->getProperties()
        ));
    }

    /**
     * @param BasicReturn $frame
     *
     * @throws NoReturnException
     */
    private function onBasicReturn(BasicReturn $frame)
    {
        $header = $this->readHeader();
        $content = $this->readContent($header->getSize());

        if (!$this->returnCallable) {
            throw new NoReturnException(
                'A message was returned but there is no return handler. Make sure you setup a handler for returned '.
                'messages using Channel::onReturn method, or remove MANDATORY and IMMEDIATE flags when publishing '.
                'messages.'
            );
        }

        call_user_func($this->returnCallable, new Returned(
            $frame->getReplyCode(),
            $frame->getReplyText(),
            $frame->getExchange(),
            $frame->getRoutingKey(),
            $content,
            $header->getProperties()
        ));
    }

    /**
     * @param BasicAck $frame
     */
    private function onBasicAck(BasicAck $frame)
    {
        $this->confirmPublishing(true, $frame->getDeliveryTag(), $frame->isMultiple());
    }

    /**
     * @param BasicNack $frame
     */
    private function onBasicNack(BasicNack $frame)
    {
        $this->confirmPublishing(false, $frame->getDeliveryTag(), $frame->isMultiple());
    }

    /**
     * @param bool   $ok
     * @param string $tag
     * @param bool   $multiple
     */
    private function confirmPublishing($ok, $tag, $multiple)
    {
        if (!$this->confirmCallable) {
            throw new \RuntimeException(
                'Something is wrong: channel is in confirm mode, but confirm callable is not set'
            );
        }

        call_user_func($this->confirmCallable, new Confirm($ok, $tag, $multiple));
    }

    /**
     * @param BasicCancel $frame
     */
    private function onBasicCancel(BasicCancel $frame)
    {
        unset($this->consumers[$frame->getConsumerTag()]);

        if (!$frame->isNoWait()) {
            $this->wire->send(new BasicCancelOk($this->id, $frame->getConsumerTag()));
        }
    }

    /**
     * @param ChannelFlow $frame
     */
    private function onChannelFlow(ChannelFlow $frame)
    {
        $this->wire->send(new ChannelFlowOk($this->id, $frame->isActive()));

        $this->status = $frame->isActive() ? self::STATUS_READY : self::STATUS_INACTIVE;
    }

    /**
     * @param ChannelClose $frame
     *
     * @throws AMQPException
     */
    private function onChannelClose(ChannelClose $frame)
    {
        $this->wire->send(new ChannelCloseOk($this->id));

        $this->status = self::STATUS_CLOSED;

        throw AMQPException::make($frame->getReplyText(), $frame->getReplyCode());
    }

    /**
     * @return Header
     */
    private function readHeader()
    {
        return $this->wire->wait($this->id, Header::class);
    }

    /**
     * @param int $size
     *
     * @return string
     */
    private function readContent($size)
    {
        $content = '';

        while ($size > strlen($content)) {
            $content .= $this->wire->wait($this->id, Content::class)
                ->getData();
        }

        return $content;
    }
}
