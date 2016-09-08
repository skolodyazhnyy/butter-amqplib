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
        return UnsignedOctetValue::encode(strlen($value)).$value;
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
