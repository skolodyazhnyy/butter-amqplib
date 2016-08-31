<?php

namespace AMQLib;

use AMQLib\Framing\Frame;

interface FrameHandlerInterface
{
    /**
     * @param Frame $frame
     */
    public function handle(Frame $frame);
}
