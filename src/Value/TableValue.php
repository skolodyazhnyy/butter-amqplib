<?php

namespace ButterAMQP\Value;

use ButterAMQP\Buffer;

class TableValue extends AbstractValue
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
            $data .= ShortStringValue::encode($key).TypifiedValue::encode($element);
        }

        return LongValue::encode(strlen($data)).$data;
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
            $elements[ShortStringValue::decode($buffer)] = TypifiedValue::decode($buffer);
        }

        return $elements;
    }
}
