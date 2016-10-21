<?php

namespace ButterAMQP\Value;

use ButterAMQP\Buffer;

class ArrayValue extends AbstractValue
{
    /**
     * @param array $value
     *
     * @return string
     */
    public static function encode($value)
    {
        $data = '';

        foreach ($value as $key => $element) {
            $data .= TypifiedValue::encode($element);
        }

        return UnsignedLongValue::encode(strlen($data)).$data;
    }

    /**
     * @param Buffer $data
     *
     * @return array
     */
    public static function decode(Buffer $data)
    {
        $length = UnsignedLongValue::decode($data);
        $buffer = new Buffer($data->read($length));
        $elements = [];

        while (!$buffer->eof()) {
            $elements[] = TypifiedValue::decode($buffer);
        }

        return $elements;
    }
}
