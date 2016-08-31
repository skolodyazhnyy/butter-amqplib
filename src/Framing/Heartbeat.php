<?php

namespace AMQLib\Framing;

use AMQLib\Buffer;

class Heartbeat extends Frame
{
    /**
     * @return string
     */
    public function encode()
    {
        return '';
    }

    /**
     * @param Buffer $data
     *
     * @return Heartbeat
     */
    public static function decode(Buffer $data)
    {
        return new self();
    }

    /**
     * {@inheritdoc}
     */
    public function getFrameType()
    {
        return "\x08";
    }
}
