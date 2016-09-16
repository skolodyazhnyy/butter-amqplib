<?php

namespace ButterAMQP\Value;

use ButterAMQP\Buffer;

class UnsignedOctetValue extends AbstractValue
{
    /**
     * @param int $value
     *
     * @return string
     */
    public static function encode($value)
    {
        return pack('C', $value);
    }

    /**
     * @param Buffer $data
     *
     * @return int
     */
    public static function decode(Buffer $data)
    {
        return unpack('C', $data->read(1))[1];
    }
}
