<?php

namespace ButterAMQP\Value;

use ButterAMQP\Buffer;

class LongStringValue extends AbstractValue
{
    /**
     * @param string $value
     *
     * @return string
     */
    public static function encode($value)
    {
        return pack('N', strlen($value)).$value;
    }

    /**
     * @param Buffer $data
     *
     * @return float
     */
    public static function decode(Buffer $data)
    {
        return $data->read(unpack('N', $data->read(4))[1]);
    }
}
