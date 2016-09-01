<?php

namespace AMQLib;

use AMQLib\Framing\Frame;

interface WireSubscriberInterface
{
    /**
     * @param Frame $frame
     */
    public function dispatch(Frame $frame);
}
