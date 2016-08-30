<?php

namespace AMQPLib;

use AMQPLib\Framing\Frame;

interface WireInterface
{
    /**
     * Send a frame to the connection.
     *
     * @param int   $channel
     * @param Frame $frame
     *
     * @return WireInterface
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
