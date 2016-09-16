<?php

namespace ButterAMQP\Value;

use ButterAMQP\Buffer;

class UnsignedShortValue extends AbstractValue
{
    /**
     * @param string $value
     *
     * @return int
     */
    public static function encode($value)
    {
        return pack('n', $value);
    }

    /**
     * @param Buffer $data
     *
     * @return int
     */
    public static function decode(Buffer $data)
    {
        return unpack('n', $data->read(2))[1];
    }
}
