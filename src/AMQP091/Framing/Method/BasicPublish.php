<?php
/*
 * This file is automatically generated.
 */

namespace ButterAMQP\AMQP091\Framing\Method;

use ButterAMQP\AMQP091\Framing\Frame;
use ButterAMQP\Value;

/**
 * Publish a message.
 *
 * @codeCoverageIgnore
 */
class BasicPublish extends Frame
{
    /**
     * @var int
     */
    private $reserved1;

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
    private $mandatory;

    /**
     * @var bool
     */
    private $immediate;

    /**
     * @param int    $channel
     * @param int    $reserved1
     * @param string $exchange
     * @param string $routingKey
     * @param bool   $mandatory
     * @param bool   $immediate
     */
    public function __construct($channel, $reserved1, $exchange, $routingKey, $mandatory, $immediate)
    {
        $this->reserved1 = $reserved1;
        $this->exchange = $exchange;
        $this->routingKey = $routingKey;
        $this->mandatory = $mandatory;
        $this->immediate = $immediate;

        parent::__construct($channel);
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
     * Indicate mandatory routing.
     *
     * @return bool
     */
    public function isMandatory()
    {
        return $this->mandatory;
    }

    /**
     * Request immediate delivery.
     *
     * @return bool
     */
    public function isImmediate()
    {
        return $this->immediate;
    }

    /**
     * @return string
     */
    public function encode()
    {
        $data = "\x00\x3C\x00\x28".
            Value\ShortValue::encode($this->reserved1).
            Value\ShortStringValue::encode($this->exchange).
            Value\ShortStringValue::encode($this->routingKey).
            Value\OctetValue::encode(($this->mandatory ? 1 : 0) | (($this->immediate ? 1 : 0) << 1));

        return "\x01".pack('nN', $this->channel, strlen($data)).$data."\xCE";
    }
}
