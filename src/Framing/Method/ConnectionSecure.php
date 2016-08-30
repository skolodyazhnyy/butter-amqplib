<?php
/*
 * This file is automatically generated.
 */

namespace AMQPLib\Framing\Method;

use AMQPLib\Buffer;
use AMQPLib\Framing\Method;
use AMQPLib\Value;

/**
 * Security mechanism challenge.
 */
class ConnectionSecure extends Method
{
    /**
     * @var string
     */
    private $challenge;

    /**
     * @param string $challenge
     */
    public function __construct($challenge)
    {
        $this->challenge = $challenge;
    }

    /**
     * Security challenge data.
     *
     * @return string
     */
    public function getChallenge()
    {
        return $this->challenge;
    }

    /**
     * @return string
     */
    public function encode()
    {
        return "\x00\x0A\x00\x14".
            Value\LongStringValue::encode($this->challenge);
    }

    /**
     * @param Buffer $data
     *
     * @return $this
     */
    public static function decode(Buffer $data)
    {
        return new self(
            Value\LongStringValue::decode($data)
        );
    }
}
