<?php

namespace AMQLib\Value;

use AMQLib\Buffer;

class BooleanValue extends AbstractValue
{
    /**
     * @param bool $value
     *
     * @return string
     */
    public static function encode($value)
    {
        return $value ? "\x01" : "\x00";
    }

    /**
     * @param Buffer $data
     *
     * @return bool
     */
    public static function decode(Buffer $data)
    {
        return $data->read(1) != "\x00";
    }
}
