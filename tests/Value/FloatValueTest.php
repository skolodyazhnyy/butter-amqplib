<?php

namespace AMQLibTest\Value;

use AMQLib\Buffer;
use AMQLib\Value\FloatValue;
use PHPUnit\Framework\TestCase;

class FloatValueTest extends TestCase
{
    public function testEncode()
    {
        self::assertEquals($this->getData(), FloatValue::encode($this->getValue()));
    }

    public function testDecode()
    {
        self::assertEquals($this->getValue(), FloatValue::decode(new Buffer($this->getData())));
    }

    private function getValue()
    {
        return 1812.6719970703125;
    }

    private function getData()
    {
        return "\x81\x95\xE2\x44";
    }
}
