<?php

namespace ButterAMQPTest;

use ButterAMQP\Buffer;
use PHPUnit\Framework\TestCase;

class BufferTest extends TestCase
{
    public function testReading()
    {
        $buffer = new Buffer("qux\0baz\r\nfoo\0bar");

        self::assertEquals('qux', $buffer->read(3));
        self::assertEquals("\0ba", $buffer->read(3));
        self::assertEquals("z\r\n", $buffer->read(3));
        self::assertEquals('foo', $buffer->read(3));
        self::assertFalse($buffer->eof());
        self::assertEquals("\0bar", $buffer->read(4));
        self::assertTrue($buffer->eof());
    }

    public function testSize()
    {
        $buffer = new Buffer("qux\0baz\r\nfoo\0bar");

        self::assertEquals(16, $buffer->size());
    }
}
