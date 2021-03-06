<?php
/*
 * This file is automatically generated.
 */

namespace ButterAMQP\AMQP091\Framing\Method;

use ButterAMQP\AMQP091\Framing\Frame;
use ButterAMQP\Value;

/**
 * @codeCoverageIgnore
 */
class ConfirmSelect extends Frame
{
    /**
     * @var bool
     */
    private $nowait;

    /**
     * @param int  $channel
     * @param bool $nowait
     */
    public function __construct($channel, $nowait)
    {
        $this->nowait = $nowait;

        parent::__construct($channel);
    }

    /**
     * Nowait.
     *
     * @return bool
     */
    public function isNowait()
    {
        return $this->nowait;
    }

    /**
     * @return string
     */
    public function encode()
    {
        $data = "\x00\x55\x00\x0A".
            Value\BooleanValue::encode($this->nowait);

        return "\x01".pack('nN', $this->channel, strlen($data)).$data."\xCE";
    }
}
