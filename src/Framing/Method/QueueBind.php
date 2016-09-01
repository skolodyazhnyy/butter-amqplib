<?php
/*
 * This file is automatically generated.
 */

namespace AMQLib\Framing\Method;

use AMQLib\Buffer;
use AMQLib\Framing\Method;
use AMQLib\Value;

/**
 * Bind queue to an exchange.
 *
 * @codeCoverageIgnore
 */
class QueueBind extends Method
{
    /**
     * @var int
     */
    private $reserved1;

    /**
     * @var string
     */
    private $queue;

    /**
     * @var string
     */
    private $exchange;

    /**
     * @var string
     */
    private $routingKey;

    /**
     * @var bool
     */
    private $noWait;

    /**
     * @var array
     */
    private $arguments = [];

    /**
     * @param int    $reserved1
     * @param string $queue
     * @param string $exchange
     * @param string $routingKey
     * @param bool   $noWait
     * @param array  $arguments
     */
    public function __construct($reserved1, $queue, $exchange, $routingKey, $noWait, $arguments)
    {
        $this->reserved1 = $reserved1;
        $this->queue = $queue;
        $this->exchange = $exchange;
        $this->routingKey = $routingKey;
        $this->noWait = $noWait;
        $this->arguments = $arguments;
    }

    /**
     * Reserved1.
     *
     * @return int
     */
    public function getReserved1()
    {
        return $this->reserved1;
    }

    /**
     * Queue.
     *
     * @return string
     */
    public function getQueue()
    {
        return $this->queue;
    }

    /**
     * Name of the exchange to bind to.
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
     * NoWait.
     *
     * @return bool
     */
    public function isNoWait()
    {
        return $this->noWait;
    }

    /**
     * Arguments for binding.
     *
     * @return array
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * @return string
     */
    public function encode()
    {
        return "\x00\x32\x00\x14".
            Value\ShortValue::encode($this->reserved1).
            Value\ShortStringValue::encode($this->queue).
            Value\ShortStringValue::encode($this->exchange).
            Value\ShortStringValue::encode($this->routingKey).
            Value\BooleanValue::encode($this->noWait).
            Value\TableValue::encode($this->arguments);
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
            Value\ShortStringValue::decode($data),
            Value\BooleanValue::decode($data),
            Value\TableValue::decode($data)
        );
    }
}
