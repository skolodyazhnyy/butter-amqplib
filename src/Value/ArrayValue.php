<?php

namespace AMQLib\Value;

use AMQLib\Binary;
use AMQLib\Buffer;

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

        return LongValue::encode(Binary::length($data)).$data;
    }

    /**
     * @param Buffer $data
     *
     * @return array
     */
    public static function decode(Buffer $data)
    {
        $length = LongValue::decode($data);
        $buffer = new Buffer($data->read($length));
        $elements = [];

        while (!$buffer->eof()) {
            $elements[] = TypifiedValue::decode($buffer);
        }

        return $elements;
    }
}
