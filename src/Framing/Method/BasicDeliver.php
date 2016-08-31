<?php
/*
 * This file is automatically generated.
 */

namespace AMQLib\Framing\Method;

use AMQLib\Buffer;
use AMQLib\Framing\Method;
use AMQLib\Value;

/**
 * Notify the client of a consumer message.
 */
class BasicDeliver extends Method
{
    /**
     * @var string
     */
    private $consumerTag;

    /**
     * @var int
     */
    private $deliveryTag;

    /**
     * @var bool
     */
    private $redelivered;

    /**
     * @var string
     */
    private $exchange;

    /**
     * @var string
     */
    private $routingKey;

    /**
     * @param string $consumerTag
     * @param int    $deliveryTag
     * @param bool   $redelivered
     * @param string $exchange
     * @param string $routingKey
     */
    public function __construct($consumerTag, $deliveryTag, $redelivered, $exchange, $routingKey)
    {
        $this->consumerTag = $consumerTag;
        $this->deliveryTag = $deliveryTag;
        $this->redelivered = $redelivered;
        $this->exchange = $exchange;
        $this->routingKey = $routingKey;
    }

    /**
     * ConsumerTag.
     *
     * @return string
     */
    public function getConsumerTag()
    {
        return $this->consumerTag;
    }

    /**
     * DeliveryTag.
     *
     * @return int
     */
    public function getDeliveryTag()
    {
        return $this->deliveryTag;
    }

    /**
     * Redelivered.
     *
     * @return bool
     */
    public function isRedelivered()
    {
        return $this->redelivered;
    }

    /**
     * Exchange.
     *
     * @return string
     */
    public function getExchange()
    {
        return $this->exchange;
    }

    /**
     * Message routing key.
     *
     * @return string
     */
    public function getRoutingKey()
    {
        return $this->routingKey;
    }

    /**
     * @return string
     */
    public function encode()
    {
        return "\x00\x3C\x00\x3C".
            Value\ShortStringValue::encode($this->consumerTag).
            Value\LongLongValue::encode($this->deliveryTag).
            Value\BooleanValue::encode($this->redelivered).
            Value\ShortStringValue::encode($this->exchange).
            Value\ShortStringValue::encode($this->routingKey);
    }

    /**
     * @param Buffer $data
     *
     * @return $this
     */
    public static function decode(Buffer $data)
    {
        return new self(
            Value\ShortStringValue::decode($data),
            Value\LongLongValue::decode($data),
            Value\BooleanValue::decode($data),
            Value\ShortStringValue::decode($data),
            Value\ShortStringValue::decode($data)
        );
    }
}
