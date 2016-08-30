<?php
/*
 * This file is automatically generated.
 */

namespace AMQPLib\Framing\Method;

use AMQPLib\Buffer;
use AMQPLib\Framing\Method;
use AMQPLib\Value;

/**
 * Return a failed message.
 */
class BasicReturn extends Method
{
    /**
     * @var int
     */
    private $replyCode;

    /**
     * @var string
     */
    private $replyText;

    /**
     * @var string
     */
    private $exchange;

    /**
     * @var string
     */
    private $routingKey;

    /**
     * @param int    $replyCode
     * @param string $replyText
     * @param string $exchange
     * @param string $routingKey
     */
    public function __construct($replyCode, $replyText, $exchange, $routingKey)
    {
        $this->replyCode = $replyCode;
        $this->replyText = $replyText;
        $this->exchange = $exchange;
        $this->routingKey = $routingKey;
    }

    /**
     * ReplyCode.
     *
     * @return int
     */
    public function getReplyCode()
    {
        return $this->replyCode;
    }

    /**
     * ReplyText.
     *
     * @return string
     */
    public function getReplyText()
    {
        return $this->replyText;
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
        return "\x00\x3C\x00\x32".
            Value\ShortValue::encode($this->replyCode).
            Value\ShortStringValue::encode($this->replyText).
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
            Value\ShortValue::decode($data),
            Value\ShortStringValue::decode($data),
            Value\ShortStringValue::decode($data),
            Value\ShortStringValue::decode($data)
        );
    }
}
