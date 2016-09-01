<?php

namespace ButterAMQPTest\Value;

use ButterAMQP\Buffer;
use ButterAMQP\Value\LongStringValue;
use PHPUnit\Framework\TestCase;

class LongStringValueTest extends TestCase
{
    public function testEncode()
    {
        self::assertEquals($this->getData(), LongStringValue::encode($this->getValue()));
    }

    public function testDecode()
    {
        self::assertEquals($this->getValue(), LongStringValue::decode(new Buffer($this->getData())));
    }

    private function getValue()
    {
        return 'hello world!';
    }

    private function getData()
    {
        return "\x00\x00\x00\x0Chello world!";
    }
}
