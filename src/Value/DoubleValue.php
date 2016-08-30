<?php

namespace AMQPLib\Value;

use AMQPLib\Binary;
use AMQPLib\Buffer;

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
