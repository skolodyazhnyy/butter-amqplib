<?php

namespace ButterAMQP\Value;

use ButterAMQP\Binary;
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
        return Binary::pack('J', $value);
    }

    /**
     * @param Buffer $data
     *
     * @return int
     */
    public static function decode(Buffer $data)
    {
        return Binary::unpack('J', $data->read(8));
    }
}
