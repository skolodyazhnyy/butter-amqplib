<?php

namespace AMQPLib\Value;

use AMQPLib\Binary;
use AMQPLib\Buffer;

class UnsignedLongValue extends AbstractValue
{
    /**
     * @param int $value
     *
     * @return string
     */
    public static function encode($value)
    {
        return Binary::pack('N', $value);
    }

    /**
     * @param Buffer $data
     *
     * @return int
     */
    public static function decode(Buffer $data)
    {
        return Binary::unpack('N', $data->read(4));
    }
}
