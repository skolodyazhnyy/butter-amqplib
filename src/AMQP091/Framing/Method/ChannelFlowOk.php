<?php
/*
 * This file is automatically generated.
 */

namespace ButterAMQP\AMQP091\Framing\Method;

use ButterAMQP\AMQP091\Framing\Frame;
use ButterAMQP\Value;

/**
 * Confirm a flow method.
 *
 * @codeCoverageIgnore
 */
class ChannelFlowOk extends Frame
{
    /**
     * @var bool
     */
    private $active;

    /**
     * @param int  $channel
     * @param bool $active
     */
    public function __construct($channel, $active)
    {
        $this->active = $active;

        parent::__construct($channel);
    }

    /**
     * Current flow setting.
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
        $data = "\x00\x14\x00\x15".
            Value\BooleanValue::encode($this->active);

        return "\x01".pack('nN', $this->channel, strlen($data)).$data."\xCE";
    }
}
