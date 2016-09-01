<?php

namespace ButterAMQP;

use ButterAMQP\Framing\Frame;

interface WireSubscriberInterface
{
    /**
     * @param Frame $frame
     */
    public function dispatch(Frame $frame);
}
