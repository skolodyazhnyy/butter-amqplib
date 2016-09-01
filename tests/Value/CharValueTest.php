<?php

namespace ButterAMQPTest\Value;

use ButterAMQP\Buffer;
use ButterAMQP\Value\CharValue;
use PHPUnit\Framework\TestCase;

class CharValueTest extends TestCase
{
    public function testEncode()
    {
        self::assertEquals("\xA1", CharValue::encode("\xA1\xA2"));
    }

    public function testDecode()
    {
        self::assertEquals("\xA1", CharValue::decode(new Buffer("\xA1\xA2")));
    }
}
