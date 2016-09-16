<?php

namespace ButterAMQP\Value;

use ButterAMQP\Buffer;

class DoubleValue extends AbstractValue
{
    /**
     * @param float $value
     *
     * @return string
     */
    public static function encode($value)
    {
        return pack('d', $value);
    }

    /**
     * @param Buffer $data
     *
     * @return float
     */
    public static function decode(Buffer $data)
    {
        return unpack('d', $data->read(8))[1];
    }
}
