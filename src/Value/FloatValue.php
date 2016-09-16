<?php

namespace ButterAMQP\Value;

use ButterAMQP\Buffer;

class FloatValue extends AbstractValue
{
    /**
     * @param float $value
     *
     * @return string
     */
    public static function encode($value)
    {
        return pack('f', $value);
    }

    /**
     * @param Buffer $data
     *
     * @return float
     */
    public static function decode(Buffer $data)
    {
        return unpack('f', $data->read(4))[1];
    }
}
