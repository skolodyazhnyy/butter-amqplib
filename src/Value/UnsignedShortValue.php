<?php

namespace AMQPLib\Value;

use AMQPLib\Binary;
use AMQPLib\Buffer;

class UnsignedShortValue extends AbstractValue
{
    /**
     * @param string $value
     *
     * @return int
     */
    public static function encode($value)
    {
        return Binary::pack('n', $value);
    }

    /**
     * @param Buffer $data
     *
     * @return int
     */
    public static function decode(Buffer $data)
    {
        return Binary::unpack('n', $data->read(2));
    }
}
