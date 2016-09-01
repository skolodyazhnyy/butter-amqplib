<?php

namespace AMQLibTest\Value;

use AMQLib\Buffer;
use AMQLib\Value\ArrayValue;
use PHPUnit\Framework\TestCase;

class ArrayValueTest extends TestCase
{
    public function testEncode()
    {
        self::assertEquals($this->getData(), ArrayValue::encode($this->getValue()));
    }

    public function testDecode()
    {
        self::assertEquals($this->getValue(), ArrayValue::decode(new Buffer($this->getData())));
    }

    private function getValue()
    {
        return ['one', 11];
    }

    private function getData()
    {
        return "\x00\x00\x00\x0DS\x00\x00\x00\x03oneI\x00\x00\x00\x0B";
    }
}
