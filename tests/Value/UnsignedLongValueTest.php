<?php

namespace AMQLibTest\Value;

use AMQLib\Buffer;
use AMQLib\Value\UnsignedLongValue;
use PHPUnit\Framework\TestCase;

class UnsignedLongValueTest extends TestCase
{
    public function testEncode()
    {
        self::assertEquals($this->getData(), UnsignedLongValue::encode($this->getValue()));
    }

    public function testDecode()
    {
        self::assertEquals($this->getValue(), UnsignedLongValue::decode(new Buffer($this->getData())));
    }

    private function getValue()
    {
        return 3051419288;
    }

    private function getData()
    {
        return "\xB5\xE0\xF6\x98";
    }
}
