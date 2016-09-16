<?php

namespace ButterAMQP\Value;

use ButterAMQP\Buffer;

class UnsignedLongValue extends AbstractValue
{
    /**
     * @param int $value
     *
     * @return string
     */
    public static function encode($value)
    {
        return pack('N', $value);
    }

    /**
     * @param Buffer $data
     *
     * @return int
     */
    public static function decode(Buffer $data)
    {
        return unpack('N', $data->read(4))[1];
    }
}
