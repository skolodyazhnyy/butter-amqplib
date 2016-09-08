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
        return LongValue::encode(strlen($value)).$value;
    }

    /**
     * @param Buffer $data
     *
     * @return float
     */
    public static function decode(Buffer $data)
    {
        $length = LongValue::decode($data);

        return $data->read($length);
    }
}
