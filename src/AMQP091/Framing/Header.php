<?php

namespace ButterAMQP\AMQP091\Framing;

use ButterAMQP\Value;

/**
 * @codeCoverageIgnore
 */
class Header extends Frame
{
    /**
     * @var int
     */
    private $classId;

    /**
     * @var int
     */
    private $weight;

    /**
     * @var int
     */
    private $size;

    /**
     * @var array
     */
    private $properties = [];

    /**
     * @param int   $channel
     * @param int   $classId
     * @param int   $weight
     * @param int   $size
     * @param array $properties
     */
    public function __construct($channel, $classId, $weight, $size, array $properties = [])
    {
        $this->classId = $classId;
        $this->weight = $weight;
        $this->size = $size;
        $this->properties = $properties;

        parent::__construct($channel);
    }

    /**
     * @return int
     */
    public function getClassId()
    {
        return $this->classId;
    }

    /**
     * @return int
     */
    public function getWeight()
    {
        return $this->weight;
    }

    /**
     * @return int
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * @return array
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * @return string
     */
    public function encode()
    {
        $flags = 0;
        $payload = '';

        if (array_key_exists('content-type', $this->properties)) {
            $flags |= 32768;
            $payload .= Value\ShortStringValue::encode($this->properties['content-type']);
        }

        if (array_key_exists('content-encoding', $this->properties)) {
            $flags |= 16384;
            $payload .= Value\ShortStringValue::encode($this->properties['content-encoding']);
        }

        if (array_key_exists('headers', $this->properties)) {
            $flags |= 8192;
            $payload .= Value\TableValue::encode($this->properties['headers']);
        }

        if (array_key_exists('delivery-mode', $this->properties)) {
            $flags |= 4096;
            $payload .= Value\OctetValue::encode($this->properties['delivery-mode']);
        }

        if (array_key_exists('priority', $this->properties)) {
            $flags |= 2048;
            $payload .= Value\OctetValue::encode($this->properties['priority']);
        }

        if (array_key_exists('correlation-id', $this->properties)) {
            $flags |= 1024;
            $payload .= Value\ShortStringValue::encode($this->properties['correlation-id']);
        }

        if (array_key_exists('reply-to', $this->properties)) {
            $flags |= 512;
            $payload .= Value\ShortStringValue::encode($this->properties['reply-to']);
        }

        if (array_key_exists('expiration', $this->properties)) {
            $flags |= 256;
            $payload .= Value\ShortStringValue::encode($this->properties['expiration']);
        }

        if (array_key_exists('message-id', $this->properties)) {
            $flags |= 128;
            $payload .= Value\ShortStringValue::encode($this->properties['message-id']);
        }

        if (array_key_exists('timestamp', $this->properties)) {
            $flags |= 64;
            $payload .= Value\LongLongValue::encode($this->properties['timestamp']);
        }

        if (array_key_exists('type', $this->properties)) {
            $flags |= 32;
            $payload .= Value\ShortStringValue::encode($this->properties['type']);
        }

        if (array_key_exists('user-id', $this->properties)) {
            $flags |= 16;
            $payload .= Value\ShortStringValue::encode($this->properties['user-id']);
        }

        if (array_key_exists('app-id', $this->properties)) {
            $flags |= 8;
            $payload .= Value\ShortStringValue::encode($this->properties['app-id']);
        }

        if (array_key_exists('reserved', $this->properties)) {
            $flags |= 4;
            $payload .= Value\ShortStringValue::encode($this->properties['reserved']);
        }

        $data = pack('nnJn', $this->classId, $this->weight, $this->size, $flags).
            $payload;

        return "\x02".pack('nN', $this->channel, strlen($data)).$data."\xCE";
    }
}
