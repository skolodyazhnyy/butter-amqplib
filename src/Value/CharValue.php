<?php

namespace ButterAMQP\Value;

use ButterAMQP\Binary;
use ButterAMQP\Buffer;

class CharValue extends AbstractValue
{
    /**
     * @param string $value
     *
     * @return string
     */
    public static function encode($value)
    {
        return Binary::subset($value, 0, 1);
    }

    /**
     * @param Buffer $data
     *
     * @return array
     */
    public static function decode(Buffer $data)
    {
        return $data->read(1);
    }
}
