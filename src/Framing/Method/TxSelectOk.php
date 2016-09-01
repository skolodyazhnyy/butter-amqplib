<?php
/*
 * This file is automatically generated.
 */

namespace AMQLib\Framing\Method;

use AMQLib\Buffer;
use AMQLib\Framing\Method;

/**
 * Confirm transaction mode.
 *
 * @codeCoverageIgnore
 */
class TxSelectOk extends Method
{
    /**
     * @return string
     */
    public function encode()
    {
        return "\x00\x5A\x00\x0B";
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
