<?php

namespace ButterAMQPTest\Value;

use ButterAMQP\Buffer;
use ButterAMQP\Value;
use ButterAMQP\Value\TypifiedValue;
use PHPUnit\Framework\TestCase;

class TypifiedValueTest extends TestCase
{
    /**
     * @param string $data
     * @param mixed  $value
     *
     * @dataProvider provideTestCases
     */
    public function testEncode($data, $value)
    {
        self::assertEquals($data, TypifiedValue::encode($value));
    }

    /**
     * Typified value encoder should throw an exception when can not guess type.
     */
    public function testEncodeTypeException()
    {
        $this->expectException(\InvalidArgumentException::class);

        TypifiedValue::encode(new \stdClass());
    }

    /**
     * @param string $data
     * @param mixed  $value
     *
     * @dataProvider provideTestCases
     */
    public function testDecode($data, $value)
    {
        $value = ($value instanceof Value\AbstractValue) ? $value->getValue() : $value;

        self::assertEquals($value, TypifiedValue::decode(new Buffer($data)));
    }

    /**
     * Typified value decoder should throw an exception when reading invalid type hint.
     */
    public function testDecodeTypeException()
    {
        $this->expectException(\InvalidArgumentException::class);

        TypifiedValue::decode(new Buffer("\xCE"));
    }

    /**
     * @return array
     */
    public function provideTestCases()
    {
        return [
            'boolean' => [
                "t\x01",
                new Value\BooleanValue(true),
            ],
            'octet' => [
                "b\x86",
                new Value\OctetValue(-122),
            ],
            'unsigned octet' => [
                "B\xB6",
                new Value\UnsignedOctetValue(182),
            ],
            'short' => [
                "U\xC9\xE5",
                new Value\ShortValue(-13851),
            ],
            'unsigned short' => [
                "u\xE5\xE3",
                new Value\UnsignedShortValue(58851),
            ],
            'long' => [
                "I\x86\x24\xB2\xF0",
                new Value\LongValue(-2044415248),
            ],
            'unsigned long' => [
                "i\xB5\xE0\xF6\x98",
                new Value\UnsignedLongValue(3051419288),
            ],
            'long long' => [
                "L\x80\x23\x86\xF2\x67\x19\x10\x00",
                new Value\LongLongValue(-9213372036999999488),
            ],
            'unsigned long long' => [
                "l\x7E\x9C\x04\x9C\xD9\x69\xB7\xFE",
                new Value\UnsignedLongLongValue(9123172016854775806),
            ],
            'float' => [
                "f\x81\x95\xE2\x44",
                new Value\FloatValue(1812.6719970703125),
            ],
            'double' => [
                "d\xFF\x94\x2A\x51\xB3\x56\xF1\x40",
                new Value\DoubleValue(71019.207316),
            ],
            'short string' => [
                "s\x0Bhello world",
                new Value\ShortStringValue('hello world'),
            ],
            'long string' => [
                "S\x00\x00\x00\x0Bhello world",
                new Value\LongStringValue('hello world'),
            ],
            'array' => [
                "A\x00\x00\x00\x0DS\x00\x00\x00\x03oneI\x00\x00\x00\x0B",
                new Value\ArrayValue(['one', 11]),
            ],
            'timestamp' => [
                "T\x00\x00\x00\x00\x57\xB9\x80\x59",
                new Value\TimestampValue(1471774809),
            ],
            'table' => [
                "F\x00\x00\x00\x19\x06stringS\x00\x00\x00\x03one\x04longI\x00\x00\x00\x0B",
                new Value\TableValue(['string' => 'one', 'long' => 11]),
            ],
            'void' => [
                'V',
                null,
            ],
            'php string' => [
                "S\x00\x00\x00\x0Bhello world",
                'hello world',
            ],
            'php array' => [
                "A\x00\x00\x00\x0DS\x00\x00\x00\x03oneI\x00\x00\x00\x0B",
                ['one', 11],
            ],
            'php table' => [
                "F\x00\x00\x00\x19\x06stringS\x00\x00\x00\x03one\x04longI\x00\x00\x00\x0B",
                ['string' => 'one', 'long' => 11],
            ],
            'php bool' => [
                "t\x01",
                true,
            ],
            'php number' => [
                "I\x86\x24\xB2\xF0",
                -2044415248,
            ],
            'php float' => [
                "f\x81\x95\xE2\x44",
                1812.6719970703125,
            ],
        ];
    }
}
