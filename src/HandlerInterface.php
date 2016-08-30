<?php

namespace AMQPLib;

use AMQPLib\Framing\Frame;

interface HandlerInterface
{
    /**
     * @param Frame $frame
     */
    public function handle(Frame $frame);
}
