<?php

namespace ButterAMQP;

use ButterAMQP\AMQP091\Framing\Frame;

interface WireSubscriberInterface
{
    /**
     * @param Frame $frame
     */
    public function dispatch(Frame $frame);
}
