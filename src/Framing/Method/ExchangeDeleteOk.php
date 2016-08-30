<?php
/*
 * This file is automatically generated.
 */

namespace AMQPLib\Framing\Method;

use AMQPLib\Buffer;
use AMQPLib\Framing\Method;

/**
 * Confirm deletion of an exchange.
 */
class ExchangeDeleteOk extends Method
{
    /**
     * @return string
     */
    public function encode()
    {
        return "\x00\x28\x00\x15";
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
