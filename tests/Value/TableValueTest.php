<?php

namespace ButterAMQPTest\Value;

use ButterAMQP\Buffer;
use ButterAMQP\Value\TableValue;
use PHPUnit\Framework\TestCase;

class TableValueTest extends TestCase
{
    public function testEncode()
    {
        self::assertEquals($this->getData(), TableValue::encode($this->getValue()));
    }

    public function testDecode()
    {
        self::assertEquals($this->getValue(), TableValue::decode(new Buffer($this->getData())));
    }

    private function getValue()
    {
        return ['string' => 'one', 'long' => 11];
    }

    private function getData()
    {
        return "\x00\x00\x00\x19\x06stringS\x00\x00\x00\x03one\x04longI\x00\x00\x00\x0B";
    }
}
