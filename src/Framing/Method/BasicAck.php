<?php
/*
 * This file is automatically generated.
 */

namespace AMQLib\Framing\Method;

use AMQLib\Buffer;
use AMQLib\Framing\Method;
use AMQLib\Value;

/**
 * Acknowledge one or more messages.
 */
class BasicAck extends Method
{
    /**
     * @var int
     */
    private $deliveryTag;

    /**
     * @var bool
     */
    private $multiple;

    /**
     * @param int  $deliveryTag
     * @param bool $multiple
     */
    public function __construct($deliveryTag, $multiple)
    {
        $this->deliveryTag = $deliveryTag;
        $this->multiple = $multiple;
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
     * Acknowledge multiple messages.
     *
     * @return bool
     */
    public function isMultiple()
    {
        return $this->multiple;
    }

    /**
     * @return string
     */
    public function encode()
    {
        return "\x00\x3C\x00\x50".
            Value\LongLongValue::encode($this->deliveryTag).
            Value\BooleanValue::encode($this->multiple);
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
            Value\BooleanValue::decode($data)
        );
    }
}
