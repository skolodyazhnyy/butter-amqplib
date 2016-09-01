<?php

namespace ButterAMQPTest\Value;

use ButterAMQP\Buffer;
use ButterAMQP\Value\ShortValue;
use PHPUnit\Framework\TestCase;

class ShortValueTest extends TestCase
{
    public function testEncode()
    {
        self::assertEquals($this->getData(), ShortValue::encode($this->getValue()));
    }

    public function testDecode()
    {
        self::assertEquals($this->getValue(), ShortValue::decode(new Buffer($this->getData())));
    }

    private function getValue()
    {
        return -13851;
    }

    private function getData()
    {
        return "\xC9\xE5";
    }
}
