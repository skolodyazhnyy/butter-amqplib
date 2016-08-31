<?php

namespace AMQLib\Value;

use AMQLib\Binary;
use AMQLib\Buffer;

class DoubleValue extends AbstractValue
{
    /**
     * @param float $value
     *
     * @return string
     */
    public static function encode($value)
    {
        return Binary::pack('d', $value);
    }

    /**
     * @param Buffer $data
     *
     * @return float
     */
    public static function decode(Buffer $data)
    {
        return Binary::unpack('d', $data->read(8));
    }
}
