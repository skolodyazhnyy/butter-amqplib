<?php
/*
 * This file is automatically generated.
 */

namespace AMQLib\Framing\Method;

use AMQLib\Buffer;
use AMQLib\Framing\Method;

/**
 * @codeCoverageIgnore
 */
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
