<?php

namespace ButterAMQPTest;

use ButterAMQP\Binary;
use PHPUnit\Framework\TestCase;

class BinaryTest extends TestCase
{
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
