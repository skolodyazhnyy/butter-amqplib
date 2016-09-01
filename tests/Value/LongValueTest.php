<?php

namespace ButterAMQPTest\Value;

use ButterAMQP\Buffer;
use ButterAMQP\Value\LongValue;
use PHPUnit\Framework\TestCase;

class LongValueTest extends TestCase
{
    public function testEncode()
    {
        self::assertEquals($this->getData(), LongValue::encode($this->getValue()));
    }

    public function testDecode()
    {
        self::assertEquals($this->getValue(), LongValue::decode(new Buffer($this->getData())));
    }

    private function getValue()
    {
        return -2044415248;
    }

    private function getData()
    {
        return "\x86\x24\xB2\xF0";
    }
}
