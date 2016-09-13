<?php

namespace ButterAMQP\Value;

use ButterAMQP\Buffer;
use ButterAMQP\Exception\AMQP\NotImplementedException;
use ButterAMQP\Value;

class TypifiedValue extends AbstractValue
{
    /**
     * @var array
     */
    private static $types = [
        't' => Value\BooleanValue::class,
        'b' => Value\OctetValue::class,
        'B' => Value\UnsignedOctetValue::class,
        'U' => Value\ShortValue::class,
        'u' => Value\UnsignedShortValue::class,
        'I' => Value\LongValue::class,
        'i' => Value\UnsignedLongValue::class,
        'L' => Value\LongLongValue::class,
        'l' => Value\UnsignedLongLongValue::class,
        'f' => Value\FloatValue::class,
        'd' => Value\DoubleValue::class,
        's' => Value\ShortStringValue::class,
        'S' => Value\LongStringValue::class,
        'A' => Value\ArrayValue::class,
        'T' => Value\TimestampValue::class,
        'F' => Value\TableValue::class,
    ];

    /**
     * @var array
     */
    private static $hints = [
        Value\BooleanValue::class => 't',
        Value\OctetValue::class => 'b',
        Value\UnsignedOctetValue::class => 'B',
        Value\ShortValue::class => 'U',
        Value\UnsignedShortValue::class => 'u',
        Value\LongValue::class => 'I',
        Value\UnsignedLongValue::class => 'i',
        Value\LongLongValue::class => 'L',
        Value\UnsignedLongLongValue::class => 'l',
        Value\FloatValue::class => 'f',
        Value\DoubleValue::class => 'd',
        Value\ShortStringValue::class => 's',
        Value\LongStringValue::class => 'S',
        Value\ArrayValue::class => 'A',
        Value\TimestampValue::class => 'T',
        Value\TableValue::class => 'F',
    ];

    /**
     * @param Buffer $data
     *
     * @return mixed
     */
    public static function decode(Buffer $data)
    {
        $hint = $data->read(1);

        if (isset(self::$types[$hint])) {
            return call_user_func(self::$types[$hint].'::decode', $data);
        }

        if ($hint === 'V') {
            return null;
        }

        throw new \InvalidArgumentException(sprintf('Invalid type hint "%s"', $hint));
    }

    /**
     * @param mixed $value
     *
     * @return string
     *
     * @throws NotImplementedException
     */
    public static function encode($value)
    {
        $hint = self::guess($value);

        if ($value instanceof AbstractValue) {
            $value = $value->getValue();
        }

        if (isset(self::$types[$hint])) {
            return $hint.call_user_func(self::$types[$hint].'::encode', $value);
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
        } elseif (is_string($value)) {
            return 'S';
        } elseif (is_array($value)) {
            return isset($value[0]) ? 'A' : 'F';
        } elseif (is_bool($value)) {
            return 't';
        } elseif (is_int($value)) {
            return 'I';
        } elseif (is_float($value)) {
            return 'f';
        } elseif (is_object($value)) {
            $type = get_class($value);

            if (isset(self::$hints[$type])) {
                return self::$hints[$type];
            }
        }

        throw new \InvalidArgumentException(sprintf(
            'Invalid value type "%s"',
            is_object($value) ? get_class($value) : gettype($value)
        ));
    }
}
