<?php
/*
 * This file is automatically generated.
 */

namespace AMQLib\Framing\Method;

use AMQLib\Buffer;
use AMQLib\Framing\Method;

/**
 * Confirm a channel close.
 */
class ChannelCloseOk extends Method
{
    /**
     * @return string
     */
    public function encode()
    {
        return "\x00\x14\x00\x29";
    }

    /**
     * @param Buffer $data
     *
     * @return $this
     */
    public static function decode(Buffer $data)
    {
        return new self();
    }
}
