<?php

namespace ButterAMQP\Value;

use ButterAMQP\Binary;
use ButterAMQP\Buffer;

class LongValue extends AbstractValue
{
    /**
     * @param int $value
     *
     * @return string
     */
    public static function encode($value)
    {
        return Binary::packbe('l', $value);
    }

    /**
     * @param Buffer $data
     *
     * @return int
     */
    public static function decode(Buffer $data)
    {
        return Binary::unpackbe('l', $data->read(4));
    }
}
