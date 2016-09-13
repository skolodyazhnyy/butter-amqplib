<?php

namespace ButterAMQP\AMQP091;

use ButterAMQP\AMQP091\Framing\Frame;

interface WireSubscriberInterface
{
    /**
     * @param Frame $frame
     */
    public function dispatch(Frame $frame);
}
