<?php

namespace ButterAMQP\Value;

use ButterAMQP\Binary;
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
        return UnsignedOctetValue::encode(Binary::length($value)).$value;
    }

    /**
     * @param Buffer $data
     *
     * @return string
     */
    public static function decode(Buffer $data)
    {
        $length = UnsignedOctetValue::decode($data);

        return $data->read($length);
    }
}
