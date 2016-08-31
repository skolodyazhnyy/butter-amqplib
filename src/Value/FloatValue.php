<?php

namespace AMQLib\Value;

use AMQLib\Binary;
use AMQLib\Buffer;

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
