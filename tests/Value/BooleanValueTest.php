<?php

namespace ButterAMQPTest\Value;

use ButterAMQP\Buffer;
use ButterAMQP\Value\BooleanValue;
use PHPUnit\Framework\TestCase;

class BooleanValueTest extends TestCase
{
    public function testEncode()
    {
        self::assertEquals("\x01", BooleanValue::encode(true));
        self::assertEquals("\x00", BooleanValue::encode(false));
    }

    public function testDecode()
    {
        self::assertEquals(true, BooleanValue::decode(new Buffer("\x01")));
        self::assertEquals(false, BooleanValue::decode(new Buffer("\x00")));
    }
}
