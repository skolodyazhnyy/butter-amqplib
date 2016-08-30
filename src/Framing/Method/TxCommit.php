<?php
/*
 * This file is automatically generated.
 */

namespace AMQPLib\Framing\Method;

use AMQPLib\Buffer;
use AMQPLib\Framing\Method;

/**
 * Commit the current transaction.
 */
class TxCommit extends Method
{
    /**
     * @return string
     */
    public function encode()
    {
        return "\x00\x5A\x00\x14";
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
