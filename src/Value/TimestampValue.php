<?php

namespace ButterAMQP\Value;

use ButterAMQP\Binary;
use ButterAMQP\Buffer;

class TimestampValue extends AbstractValue
{
    /**
     * @param int $value
     *
     * @return string
     */
    public static function encode($value)
    {
        return Binary::packbe('q', $value);
    }

    /**
     * @param Buffer $data
     *
     * @return string
     */
    public static function decode(Buffer $data)
    {
        return Binary::unpackbe('q', $data->read(8));
    }
}
