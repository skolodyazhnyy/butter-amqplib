<?php

namespace ButterAMQP;

class Delivery extends Message
{
    /**
     * @var ChannelInterface
     */
    private $channel;

    /**
     * @var string
     */
    private $consumerTag;

    /**
     * @var string
     */
    private $deliveryTag;

    /**
     * @var bool
     */
    private $redeliver;

    /**
     * @var string
     */
    private $exchange;

    /**
     * @var string
     */
    private $routingKey;

    /**
     * @param ChannelInterface $channel
     * @param string           $consumerTag
     * @param string           $deliveryTag
     * @param bool             $redeliver
     * @param string           $exchange
     * @param string           $routingKey
     * @param string           $body
     * @param array            $properties
     */
    public function __construct(
        ChannelInterface $channel,
        $consumerTag,
        $deliveryTag,
        $redeliver,
        $exchange,
        $routingKey,
        $body,
        array $properties
    ) {
        $this->channel = $channel;
        $this->consumerTag = $consumerTag;
        $this->deliveryTag = $deliveryTag;
        $this->redeliver = $redeliver;
        $this->exchange = $exchange;
        $this->routingKey = $routingKey;

        parent::__construct($body, $properties);
    }

    /**
     * Acknowledge message, marking it as one successfully processed by consumer.
     *
     * @param bool $multiple
     *
     * @return $this
     */
    public function ack($multiple = false)
    {
        $this->channel->ack($this->deliveryTag, $multiple);

        return $this;
    }

    /**
     * Reject message(s) marking it as one which consumer fail to process.
     *
     * @param bool $requeue  makes AMQP server put messages back to the queue
     * @param bool $multiple reject all delivered and not acknowledged messages including current one
     *
     * @return $this
     */
    public function reject($requeue = true, $multiple = false)
    {
        $this->channel->reject($this->deliveryTag, $requeue, $multiple);

        return $this;
    }

    /**
     * Cancel message consuming.
     *
     * @return $this
     */
    public function cancel()
    {
        if (empty($this->consumerTag)) {
            throw new \LogicException('Consumer is not assigned to this delivery.');
        }

        $this->channel->cancel($this->consumerTag);

        return $this;
    }

    /**
     * Consume tag - unique identifier of the consumer within a channel.
     * Used to identify consumer in frames related to it, like basic.cancel.
     *
     * @return string
     */
    public function getConsumerTag()
    {
        return $this->consumerTag;
    }

    /**
     * Delivery tag - unique identifier of the delivery within a channel.
     * Used to identify delivery in frames related to it, like basic.ack or basic.reject.
     *
     * @return string
     */
    public function getDeliveryTag()
    {
        return $this->deliveryTag;
    }

    /**
     * Redeliver is true if message was rejected before with re-enqueue set to true.
     *
     * @return bool
     */
    public function isRedeliver()
    {
        return $this->redeliver;
    }

    /**
     * Exchange where message was sent initially.
     *
     * @return string
     */
    public function getExchange()
    {
        return $this->exchange;
    }

    /**
     * Routing key.
     *
     * @return string
     */
    public function getRoutingKey()
    {
        return $this->routingKey;
    }
}
