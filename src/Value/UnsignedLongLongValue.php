<?php

namespace ButterAMQP\Value;

use ButterAMQP\Buffer;

class UnsignedLongLongValue extends AbstractValue
{
    /**
     * @param string $value
     *
     * @return int
     */
    public static function encode($value)
    {
        return pack('J', $value);
    }

    /**
     * @param Buffer $data
     *
     * @return int
     */
    public static function decode(Buffer $data)
    {
        return unpack('J', $data->read(8))[1];
    }
}
