<?php
/*
 * This file is automatically generated.
 */

namespace AMQPLib\Framing\Method;

use AMQPLib\Buffer;
use AMQPLib\Framing\Method;
use AMQPLib\Value;

/**
 * Bind exchange to an exchange.
 */
class ExchangeBind extends Method
{
    /**
     * @var int
     */
    private $reserved1;

    /**
     * @var string
     */
    private $destination;

    /**
     * @var string
     */
    private $source;

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
     * @param string $destination
     * @param string $source
     * @param string $routingKey
     * @param bool   $noWait
     * @param array  $arguments
     */
    public function __construct($reserved1, $destination, $source, $routingKey, $noWait, $arguments)
    {
        $this->reserved1 = $reserved1;
        $this->destination = $destination;
        $this->source = $source;
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
     * Name of the destination exchange to bind to.
     *
     * @return string
     */
    public function getDestination()
    {
        return $this->destination;
    }

    /**
     * Name of the source exchange to bind to.
     *
     * @return string
     */
    public function getSource()
    {
        return $this->source;
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
        return "\x00\x28\x00\x1E".
            Value\ShortValue::encode($this->reserved1).
            Value\ShortStringValue::encode($this->destination).
            Value\ShortStringValue::encode($this->source).
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
