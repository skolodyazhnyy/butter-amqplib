<?php

namespace AMQLibTest\Value;

use AMQLib\Buffer;
use AMQLib\Value\UnsignedShortValue;
use PHPUnit\Framework\TestCase;

class UnsignedShortValueTest extends TestCase
{
    public function testEncode()
    {
        self::assertEquals($this->getData(), UnsignedShortValue::encode($this->getValue()));
    }

    public function testDecode()
    {
        self::assertEquals($this->getValue(), UnsignedShortValue::decode(new Buffer($this->getData())));
    }

    private function getValue()
    {
        return 58851;
    }

    private function getData()
    {
        return "\xE5\xE3";
    }
}
