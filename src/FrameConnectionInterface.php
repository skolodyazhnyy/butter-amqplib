<?php

namespace AMQLib;

use AMQLib\Framing\Frame;

interface FrameConnectionInterface
{
    /**
     * Send a frame to the connection.
     *
     * @param int   $channel
     * @param Frame $frame
     *
     * @return FrameConnectionInterface
     */
    public function send($channel, Frame $frame);

    /**
     * @param int    $channel
     * @param string $type
     *
     * @return Frame
     */
    public function wait($channel, $type);
}
