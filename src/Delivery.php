<?php

namespace AMQPLib;

class Delivery extends Message
{
    /**
     * @var Channel
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
     * @param Channel $channel
     * @param string  $consumerTag
     * @param string  $deliveryTag
     * @param bool    $redeliver
     * @param string  $exchange
     * @param string  $routingKey
     * @param string  $body
     * @param array   $properties
     */
    public function __construct(
        Channel $channel,
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
     * @return $this
     */
    public function ack()
    {
        $this->channel->ack($this->deliveryTag);

        return $this;
    }

    /**
     * @param bool $requeue
     *
     * @return $this
     */
    public function reject($requeue = true)
    {
        $this->channel->reject($this->deliveryTag, $requeue);

        return $this;
    }

    /**
     * @return $this
     */
    public function cancel()
    {
        $this->channel->cancel($this->consumerTag);

        return $this;
    }

    /**
     * @return string
     */
    public function getConsumerTag()
    {
        return $this->consumerTag;
    }

    /**
     * @return string
     */
    public function getDeliveryTag()
    {
        return $this->deliveryTag;
    }

    /**
     * @return bool
     */
    public function isRedeliver()
    {
        return $this->redeliver;
    }
}
