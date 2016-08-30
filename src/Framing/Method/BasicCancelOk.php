<?php
/*
 * This file is automatically generated.
 */

namespace AMQPLib\Framing\Method;

use AMQPLib\Buffer;
use AMQPLib\Framing\Method;
use AMQPLib\Value;

/**
 * Confirm a cancelled consumer.
 */
class BasicCancelOk extends Method
{
    /**
     * @var string
     */
    private $consumerTag;

    /**
     * @param string $consumerTag
     */
    public function __construct($consumerTag)
    {
        $this->consumerTag = $consumerTag;
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
     * @return string
     */
    public function encode()
    {
        return "\x00\x3C\x00\x1F".
            Value\ShortStringValue::encode($this->consumerTag);
    }

    /**
     * @param Buffer $data
     *
     * @return $this
     */
    public static function decode(Buffer $data)
    {
        return new self(
            Value\ShortStringValue::decode($data)
        );
    }
}
