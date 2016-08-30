<?php

namespace AMQPLib\Value;

use AMQPLib\Binary;
use AMQPLib\Buffer;

class FloatValue extends AbstractValue
{
    /**
     * @param float $value
     *
     * @return string
     */
    public static function encode($value)
    {
        return Binary::pack('f', $value);
    }

    /**
     * @param Buffer $data
     *
     * @return float
     */
    public static function decode(Buffer $data)
    {
        return Binary::unpack('f', $data->read(4));
    }
}
