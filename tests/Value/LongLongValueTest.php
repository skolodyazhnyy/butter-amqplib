<?php

namespace AMQLibTest\Value;

use AMQLib\Buffer;
use AMQLib\Value\LongLongValue;
use PHPUnit\Framework\TestCase;

class LongLongValueTest extends TestCase
{
    public function testEncode()
    {
        self::assertEquals($this->getData(), LongLongValue::encode($this->getValue()));
    }

    public function testDecode()
    {
        self::assertEquals($this->getValue(), LongLongValue::decode(new Buffer($this->getData())));
    }

    private function getValue()
    {
        return -9213372036999999488;
    }

    private function getData()
    {
        return "\x80\x23\x86\xF2\x67\x19\x10\x00";
    }
}
