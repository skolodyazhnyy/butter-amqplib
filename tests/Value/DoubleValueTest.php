<?php

namespace ButterAMQPTest\Value;

use ButterAMQP\Buffer;
use ButterAMQP\Value\DoubleValue;
use PHPUnit\Framework\TestCase;

class DoubleValueTest extends TestCase
{
    public function testEncode()
    {
        self::assertEquals($this->getData(), DoubleValue::encode($this->getValue()));
    }

    public function testDecode()
    {
        self::assertEquals($this->getValue(), DoubleValue::decode(new Buffer($this->getData())));
    }

    private function getValue()
    {
        return 71019.207316;
    }

    private function getData()
    {
        return "\xFF\x94\x2A\x51\xB3\x56\xF1\x40";
    }
}
