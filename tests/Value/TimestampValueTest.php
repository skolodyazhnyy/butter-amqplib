<?php

namespace AMQLibTest\Value;

use AMQLib\Buffer;
use AMQLib\Value\TimestampValue;
use PHPUnit\Framework\TestCase;

class TimestampValueTest extends TestCase
{
    public function testEncode()
    {
        self::assertEquals($this->getData(), TimestampValue::encode($this->getValue()));
    }

    public function testDecode()
    {
        self::assertEquals($this->getValue(), TimestampValue::decode(new Buffer($this->getData())));
    }

    private function getValue()
    {
        return 1471774809;
    }

    private function getData()
    {
        return "\x00\x00\x00\x00\x57\xB9\x80\x59";
    }
}
