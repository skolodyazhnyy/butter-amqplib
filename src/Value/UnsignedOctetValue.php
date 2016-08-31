<?php

namespace AMQLib\Value;

use AMQLib\Binary;
use AMQLib\Buffer;

class UnsignedOctetValue extends AbstractValue
{
    /**
     * @param int $value
     *
     * @return string
     */
    public static function encode($value)
    {
        return Binary::pack('C', $value);
    }

    /**
     * @param Buffer $data
     *
     * @return int
     */
    public static function decode(Buffer $data)
    {
        return Binary::unpack('C', $data->read(1));
    }
}
