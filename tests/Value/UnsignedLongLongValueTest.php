<?php

namespace ButterAMQPTest\Value;

use ButterAMQP\Buffer;
use ButterAMQP\Value\UnsignedLongLongValue;
use PHPUnit\Framework\TestCase;

class UnsignedLongLongValueTest extends TestCase
{
    public function testEncode()
    {
        self::assertEquals($this->getData(), UnsignedLongLongValue::encode($this->getValue()));
    }

    public function testDecode()
    {
        self::assertEquals($this->getValue(), UnsignedLongLongValue::decode(new Buffer($this->getData())));
    }

    private function getValue()
    {
        return 9123172016854775806;
    }

    private function getData()
    {
        return "\x7E\x9C\x04\x9C\xD9\x69\xB7\xFE";
    }
}
