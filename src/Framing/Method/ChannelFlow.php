<?php
/*
 * This file is automatically generated.
 */

namespace AMQLib\Framing\Method;

use AMQLib\Buffer;
use AMQLib\Framing\Method;
use AMQLib\Value;

/**
 * Enable/disable flow from peer.
 */
class ChannelFlow extends Method
{
    /**
     * @var bool
     */
    private $active;

    /**
     * @param bool $active
     */
    public function __construct($active)
    {
        $this->active = $active;
    }

    /**
     * Start/stop content frames.
     *
     * @return bool
     */
    public function isActive()
    {
        return $this->active;
    }

    /**
     * @return string
     */
    public function encode()
    {
        return "\x00\x14\x00\x14".
            Value\BooleanValue::encode($this->active);
    }

    /**
     * @param Buffer $data
     *
     * @return $this
     */
    public static function decode(Buffer $data)
    {
        return new self(
            Value\BooleanValue::decode($data)
        );
    }
}
