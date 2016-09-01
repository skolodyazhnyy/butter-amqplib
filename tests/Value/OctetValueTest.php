<?php

namespace ButterAMQPTest\Value;

use ButterAMQP\Buffer;
use ButterAMQP\Value\OctetValue;
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
