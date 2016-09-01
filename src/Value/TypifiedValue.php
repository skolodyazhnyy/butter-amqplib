<?php

namespace AMQLib\Value;

use AMQLib\Buffer;
use AMQLib\Value;

class TypifiedValue extends AbstractValue
{
    /**
     * @param Buffer $data
     *
     * @return mixed
     */
    public static function decode(Buffer $data)
    {
        $hint = $data->read(1);

        switch ($hint) {
            case 't': return Value\BooleanValue::decode($data);
            case 'b': return Value\OctetValue::decode($data);
            case 'B': return Value\UnsignedOctetValue::decode($data);
            case 'U': return Value\ShortValue::decode($data);
            case 'u': return Value\UnsignedShortValue::decode($data);
            case 'I': return Value\LongValue::decode($data);
            case 'i': return Value\UnsignedLongValue::decode($data);
            case 'L': return Value\LongLongValue::decode($data);
            case 'l': return Value\UnsignedLongLongValue::decode($data);
            case 'f': return Value\FloatValue::decode($data);
            case 'd': return Value\DoubleValue::decode($data);
            case 's': return Value\ShortStringValue::decode($data);
            case 'S': return Value\LongStringValue::decode($data);
            case 'A': return Value\ArrayValue::decode($data);
            case 'T': return Value\TimestampValue::decode($data);
            case 'F': return Value\TableValue::decode($data);
            // todo: implement decimals 'D'
        }

        throw new \InvalidArgumentException(sprintf('Invalid type hint "%s"', $hint));
    }

    /**
     * @param mixed $value
     *
     * @return string
     */
    public static function encode($value)
    {
        $hint = self::guess($value);

        if ($value instanceof AbstractValue) {
            $value = $value->getValue();
        }

        switch ($hint) {
            case 't': return 't'.Value\BooleanValue::encode((bool) $value);
            case 'b': return 'b'.Value\OctetValue::encode((int) $value);
            case 'B': return 'B'.Value\UnsignedOctetValue::encode((int) $value);
            case 'U': return 'U'.Value\ShortValue::encode((int) $value);
            case 'u': return 'u'.Value\UnsignedShortValue::encode((int) $value);
            case 'I': return 'I'.Value\LongValue::encode((int) $value);
            case 'i': return 'i'.Value\UnsignedLongValue::encode((int) $value);
            case 'L': return 'L'.Value\LongLongValue::encode((int) $value);
            case 'l': return 'l'.Value\UnsignedLongLongValue::encode((int) $value);
            case 'f': return 'f'.Value\FloatValue::encode((float) $value);
            case 'd': return 'd'.Value\DoubleValue::encode((float) $value);
            case 's': return 's'.Value\ShortStringValue::encode((string) $value);
            case 'S': return 'S'.Value\LongStringValue::encode((string) $value);
            case 'A': return 'A'.Value\ArrayValue::encode((array) $value);
            case 'T': return 'T'.Value\TimestampValue::encode((int) $value);
            case 'F': return 'F'.Value\TableValue::encode((array) $value);
            case 'D': throw new \InvalidArgumentException('Decimal is not implemented');
        }

        return 'V';
    }

    /**
     * Guess value type hint.
     *
     * @param mixed $value
     *
     * @return string
     */
    private static function guess($value)
    {
        if ($value === null) {
            return 'V';
        }

        if (is_string($value)) {
            return 'S';
        }

        if (is_array($value) && isset($value[0])) {
            return 'A';
        }

        if (is_array($value)) {
            return 'F';
        }

        if (is_bool($value)) {
            return 't';
        }

        if (is_int($value)) {
            return 'I';
        }

        if (is_float($value)) {
            return 'f';
        }

        if (is_object($value)) {
            switch (get_class($value)) {
                case Value\BooleanValue::class: return 't';
                case Value\OctetValue::class: return 'b';
                case Value\UnsignedOctetValue::class: return 'B';
                case Value\ShortValue::class: return 'U';
                case Value\UnsignedShortValue::class: return 'u';
                case Value\LongValue::class: return 'I';
                case Value\UnsignedLongValue::class: return 'i';
                case Value\LongLongValue::class: return 'L';
                case Value\UnsignedLongLongValue::class: return 'l';
                case Value\FloatValue::class: return 'f';
                case Value\DoubleValue::class: return 'd';
                case Value\ShortStringValue::class: return 's';
                case Value\LongStringValue::class: return 'S';
                case Value\ArrayValue::class: return 'A';
                case Value\TimestampValue::class: return 'T';
                case Value\TableValue::class: return 'F';
                // @todo: implement "decimal"
            }

            throw new \InvalidArgumentException(sprintf('Invalid object type "%s"', get_class($value)));
        }

        throw new \InvalidArgumentException(sprintf('Invalid value type "%s"', gettype($value)));
    }
}
