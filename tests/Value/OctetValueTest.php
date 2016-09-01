<?php

namespace AMQLibTest\Value;

use AMQLib\Buffer;
use AMQLib\Value\OctetValue;
use PHPUnit\Framework\TestCase;

class OctetValueTest extends TestCase
{
    public function testEncode()
    {
        self::assertEquals($this->getData(), OctetValue::encode($this->getValue()));
    }

    public function testDecode()
    {
        self::assertEquals($this->getValue(), OctetValue::decode(new Buffer($this->getData())));
    }

    private function getValue()
    {
        return -122;
    }

    private function getData()
    {
        return "\x86";
    }
}
