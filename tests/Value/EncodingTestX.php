<?php

namespace AMQLibTest\Value;

use AMQLib\Buffer;
use AMQLib\Value;
use AMQLib\ValueInterface;

class EncodingTestX extends \PHPUnit_Framework_TestCase
{
    /**
     * @param ValueInterface $value
     * @param string         $expected
     *
     * @dataProvider provideSerializerTestCases
     */
    public function testSerialize(ValueInterface $value, $expected)
    {
        $actual = $value->serialize();

        $this->assertEquals($expected, $actual, sprintf(
            'Expected value "%s" does not match "%s"',
            $this->getHexValue($expected),
            $this->getHexValue($actual)
        ));
    }

    /**
     * @param object $value
     * @param string $data
     *
     * @dataProvider provideSerializerTestCases
     */
    public function testUnserialize($value, $data)
    {
        $class = get_class($value);

        $decoded = $class::unserialize(new Buffer($data));

        $this->assertEquals($value, $decoded);
    }

    /**
     * @return array
     */
    public function provideSerializerTestCases()
    {
        $data = [
            'string' => 'one',
            'long' => 11,
        ];

        return [
            'array' => [
                Value::set($data),
                "\x00\x00\x00\x0DS\x00\x00\x00\x03oneI\x00\x00\x00\x0B",
            ],
            'boolean' => [
                Value::bool(true),
                "\x01",
            ],
            'octet' => [
                Value::octet(-120),
                "\x88",
            ],
            'uoctet' => [
                Value::uoctet(200),
                "\xC8",
            ],
            'short' => [
                Value::short(-13851),
                "\xC9\xE5",
            ],
            'ushort' => [
                Value::ushort(58851),
                "\xE5\xE3",
            ],
            'long' => [
                Value::long(-2044415248),
                "\x86\x24\xB2\xF0",
            ],
            'ulong' => [
                Value::ulong(3051419288),
                "\xB5\xE0\xF6\x98",
            ],
            'longlong' => [
                Value::longlong(-9213372036999999488),
                "\x80\x23\x86\xF2\x67\x19\x10\x00",
            ],
            'ulonglong' => [
                Value::ulonglong(9123172016854775806),
                "\x7E\x9C\x04\x9C\xD9\x69\xB7\xFE",
            ],
            'float' => [
                Value::float(1812.6719970703125),
                "\x81\x95\xE2\x44",
            ],
            'double' => [
                Value::double(71019.207316),
                "\xFF\x94\x2A\x51\xB3\x56\xF1\x40",
            ],
            'char' => [
                Value::char("\x91"),
                "\x91",
            ],
            'shortstr' => [
                Value::shortstr('hello world!'),
                "\x0Chello world!",
            ],
            'longstr' => [
                Value::longstr('hello world!'),
                "\x00\x00\x00\x0Chello world!",
            ],
            'table' => [
                Value::table($data),
                "\x00\x00\x00\x19\x06stringS\x00\x00\x00\x03one\x04longI\x00\x00\x00\x0B",
            ],
            'timestamp' => [
                Value::timestamp(1471774809),
                "\x00\x00\x00\x00\x57\xB9\x80\x59",
            ],
            'typified-array' => [
                new Value\TypifiedValue(array_values($data)),
                "A\x00\x00\x00\x0DS\x00\x00\x00\x03oneI\x00\x00\x00\x0B",
            ],
            'typified-boolean' => [
                new Value\TypifiedValue(true),
                "t\x01",
            ],
            'typified-number' => [
                new Value\TypifiedValue(15),
                "I\x00\x00\x00\x0F",
            ],
            'typified-float' => [
                new Value\TypifiedValue(1812.6719970703125),
                "f\x81\x95\xE2\x44",
            ],
            'typified-string' => [
                new Value\TypifiedValue('hello world!'),
                "S\x00\x00\x00\x0Chello world!",
            ],
            'typified-table' => [
                new Value\TypifiedValue($data),
                "F\x00\x00\x00\x19\x06stringS\x00\x00\x00\x03one\x04longI\x00\x00\x00\x0B",
            ],
        ];
    }

    /**
     * @param string $data
     *
     * @return string
     */
    private function getHexValue($data)
    {
        $hex = [];
        for ($x = 0; $x < mb_strlen($data, 'ASCII'); ++$x) {
            $hex[] = str_pad(strtoupper(dechex(ord($data[$x]))), 2, '0', STR_PAD_LEFT);
        }

        return implode(' ', $hex);
    }
}
