<?php

namespace AMQPLib\Value;

use AMQPLib\Binary;
use AMQPLib\Buffer;

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
