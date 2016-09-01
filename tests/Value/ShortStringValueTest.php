<?php

namespace AMQLibTest\Value;

use AMQLib\Buffer;
use AMQLib\Value\ShortStringValue;
use PHPUnit\Framework\TestCase;

class ShortStringValueTest extends TestCase
{
    public function testEncode()
    {
        self::assertEquals($this->getData(), ShortStringValue::encode($this->getValue()));
    }

    public function testDecode()
    {
        self::assertEquals($this->getValue(), ShortStringValue::decode(new Buffer($this->getData())));
    }

    private function getValue()
    {
        return 'hello world!';
    }

    private function getData()
    {
        return "\x0Chello world!";
    }
}
