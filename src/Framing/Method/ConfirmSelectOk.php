<?php
/*
 * This file is automatically generated.
 */

namespace AMQPLib\Framing\Method;

use AMQPLib\Buffer;
use AMQPLib\Framing\Method;

class ConfirmSelectOk extends Method
{
    /**
     * @return string
     */
    public function encode()
    {
        return "\x00\x55\x00\x0B";
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
