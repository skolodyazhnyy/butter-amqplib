<?php

namespace AMQLibTest;

use AMQLib\Binary;
use PHPUnit\Framework\TestCase;

class BinaryTest extends TestCase
{
    /**
     * @param string $format
     * @param int    $number
     * @param string $binary
     *
     * @dataProvider providePackingTestCases
     */
    public function testPacking($format, $number, $binary)
    {
        $packed = Binary::pack($format, $number);

        self::assertEquals($binary, $packed);
    }

    /**
     * @param string $format
     * @param int    $number
     * @param string $binary
     *
     * @dataProvider providePackingTestCases
     */
    public function testUnpacking($format, $number, $binary)
    {
        $unpacked = Binary::unpack($format, $binary);

        self::assertEquals($unpacked, $number);
    }

    /**
     * @param string $format
     * @param int    $number
     * @param string $binary
     *
     * @dataProvider provideBigEndianPackingTestCases
     */
    public function testPackingBigEndian($format, $number, $binary)
    {
        $packed = Binary::packbe($format, $number);

        self::assertEquals($binary, $packed);
    }

    /**
     * @param string $format
     * @param int    $number
     * @param string $binary
     *
     * @dataProvider provideBigEndianPackingTestCases
     */
    public function testUnpackingBigEndian($format, $number, $binary)
    {
        $unpacked = Binary::unpackbe($format, $binary);

        self::assertEquals($unpacked, $number);
    }

    /**
     * Binary::length should give size of the binary buffer no matter what symbols are there.
     */
    public function testLength()
    {
        self::assertEquals(10, Binary::length("\r\n\0abc\0def"));
    }

    /**
     * Binary::subset should return sub set of the binary buffer.
     */
    public function testSubset()
    {
        self::assertEquals("\0abc", Binary::subset("\r\n\0abc\0def", 2, 4));
        self::assertEquals("\0abc\0d", Binary::subset("\r\n\0abc\0def", 2, -2));
        self::assertEquals('def', Binary::subset("\r\n\0abc\0def", -3));
        self::assertEquals("\r\n\0abc\0", Binary::subset("\r\n\0abc\0def", 0, -3));
    }

    /**
     * @return array
     */
    public function providePackingTestCases()
    {
        return [
            'unsigned octet' => ['C', 0xFF, "\xFF"],
            'unsigned short' => ['n', 0xFFAB, "\xFF\xAB"],
            'unsigned long' => ['N', 0xFA41FFAB, "\xFA\x41\xFF\xAB"],
            'unsigned long long' => ['J', 0x013F3AB13A31517A, "\x01\x3F\x3A\xB1\x3A\x31\x51\x7A"],
        ];
    }

    /**
     * @return array
     */
    public function provideBigEndianPackingTestCases()
    {
        return [
            'octet' => ['c', 0x51, "\x51"],
            'short' => ['s', 0x517A, "\x51\x7A"],
            'long' => ['l', 0x3A31517A, "\x3A\x31\x51\x7A"],
            'long long' => ['q', 0x013F3AB13A31517A, "\x01\x3F\x3A\xB1\x3A\x31\x51\x7A"],
        ];
    }
}
