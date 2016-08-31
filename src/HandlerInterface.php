<?php

namespace AMQLib;

use AMQLib\Framing\Frame;

interface HandlerInterface
{
    /**
     * @param Frame $frame
     */
    public function handle(Frame $frame);
}
