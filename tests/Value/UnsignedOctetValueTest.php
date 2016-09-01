<?php

namespace ButterAMQPTest\Value;

use ButterAMQP\Buffer;
use ButterAMQP\Value\UnsignedOctetValue;
use PHPUnit\Framework\TestCase;

class UnsignedOctetValueTest extends TestCase
{
    public function testEncode()
    {
        self::assertEquals($this->getData(), UnsignedOctetValue::encode($this->getValue()));
    }

    public function testDecode()
    {
        self::assertEquals($this->getValue(), UnsignedOctetValue::decode(new Buffer($this->getData())));
    }

    private function getValue()
    {
        return 182;
    }

    private function getData()
    {
        return "\xB6";
    }
}
