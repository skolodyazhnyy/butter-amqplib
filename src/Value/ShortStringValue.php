<?php

namespace ButterAMQP\Value;

use ButterAMQP\Buffer;

class ShortStringValue extends AbstractValue
{
    /**
     * @param string $value
     *
     * @return string
     */
    public static function encode($value)
    {
        return pack('C', strlen($value)).$value;
    }

    /**
     * @param Buffer $data
     *
     * @return string
     */
    public static function decode(Buffer $data)
    {
        return $data->read(unpack('C', $data->read(1))[1]);
    }
}
