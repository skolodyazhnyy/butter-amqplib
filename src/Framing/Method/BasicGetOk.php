<?php
/*
 * This file is automatically generated.
 */

namespace AMQLib\Framing\Method;

use AMQLib\Buffer;
use AMQLib\Framing\Method;
use AMQLib\Value;

/**
 * Provide client with a message.
 *
 * @codeCoverageIgnore
 */
class BasicGetOk extends Method
{
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
     * @var int
     */
    private $messageCount;

    /**
     * @param int    $deliveryTag
     * @param bool   $redelivered
     * @param string $exchange
     * @param string $routingKey
     * @param int    $messageCount
     */
    public function __construct($deliveryTag, $redelivered, $exchange, $routingKey, $messageCount)
    {
        $this->deliveryTag = $deliveryTag;
        $this->redelivered = $redelivered;
        $this->exchange = $exchange;
        $this->routingKey = $routingKey;
        $this->messageCount = $messageCount;
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
     * MessageCount.
     *
     * @return int
     */
    public function getMessageCount()
    {
        return $this->messageCount;
    }

    /**
     * @return string
     */
    public function encode()
    {
        return "\x00\x3C\x00\x47".
            Value\LongLongValue::encode($this->deliveryTag).
            Value\BooleanValue::encode($this->redelivered).
            Value\ShortStringValue::encode($this->exchange).
            Value\ShortStringValue::encode($this->routingKey).
            Value\LongValue::encode($this->messageCount);
    }

    /**
     * @param Buffer $data
     *
     * @return $this
     */
    public static function decode(Buffer $data)
    {
        return new self(
            Value\LongLongValue::decode($data),
            Value\BooleanValue::decode($data),
            Value\ShortStringValue::decode($data),
            Value\ShortStringValue::decode($data),
            Value\LongValue::decode($data)
        );
    }
}
