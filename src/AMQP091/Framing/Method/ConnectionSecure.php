<?php
/*
 * This file is automatically generated.
 */

namespace ButterAMQP\AMQP091\Framing\Method;

use ButterAMQP\AMQP091\Framing\Frame;
use ButterAMQP\Value;

/**
 * Security mechanism challenge.
 *
 * @codeCoverageIgnore
 */
class ConnectionSecure extends Frame
{
    /**
     * @var string
     */
    private $challenge;

    /**
     * @param int    $channel
     * @param string $challenge
     */
    public function __construct($channel, $challenge)
    {
        $this->challenge = $challenge;

        parent::__construct($channel);
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
        $data = "\x00\x0A\x00\x14".
            Value\LongStringValue::encode($this->challenge);

        return "\x01".pack('nN', $this->channel, strlen($data)).$data."\xCE";
    }
}
