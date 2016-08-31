<?php

namespace AMQLib\Value;

use AMQLib\Binary;
use AMQLib\Buffer;

class OctetValue extends AbstractValue
{
    /**
     * @param int $value
     *
     * @return string
     */
    public static function encode($value)
    {
        return Binary::packbe('c', $value);
    }

    /**
     * @param Buffer $data
     *
     * @return int
     */
    public static function decode(Buffer $data)
    {
        return Binary::unpackbe('c', $data->read(1));
    }
}
