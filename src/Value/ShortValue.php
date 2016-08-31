<?php

namespace AMQLib\Value;

use AMQLib\Binary;
use AMQLib\Buffer;

class ShortValue extends AbstractValue
{
    /**
     * @param int $value
     *
     * @return string
     */
    public static function encode($value)
    {
        return Binary::packbe('s', $value);
    }

    /**
     * @param Buffer $data
     *
     * @return int
     */
    public static function decode(Buffer $data)
    {
        return Binary::unpackbe('s', $data->read(2));
    }
}
