<?php

namespace AMQLib;

use AMQLib\Framing\Frame;

interface FrameChannelInterface
{
    /**
     * Send a frame to the channel.
     *
     * @param Frame $frame
     *
     * @return FrameChannelInterface
     */
    public function send(Frame $frame);

    /**
     * @param string $type
     *
     * @return Frame
     */
    public function wait($type);
}
