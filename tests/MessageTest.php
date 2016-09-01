<?php

namespace ButterAMQPTest;

use ButterAMQP\Message;
use PHPUnit\Framework\TestCase;

class MessageTest extends TestCase
{
    public function testBody()
    {
        $message = new Message('foo');

        self::assertEquals('foo', $message->getBody());
    }

    public function testGetProperty()
    {
        $message = new Message('', ['foo' => 'bar']);

        self::assertTrue($message->hasProperty('foo'));
        self::assertEquals('bar', $message->getProperty('foo'));
        self::assertEquals('bar', $message->getProperty('foo', 'boom'));

        self::assertFalse($message->hasProperty('baz'));
        self::assertEquals(null, $message->getProperty('baz'));
        self::assertEquals('boom', $message->getProperty('baz', 'boom'));
    }

    public function testGetHeader()
    {
        $message = new Message('', ['headers' => ['foo' => 'bar']]);

        self::assertTrue($message->hasHeader('foo'));
        self::assertEquals('bar', $message->getHeader('foo'));
        self::assertEquals('bar', $message->getHeader('foo', 'boom'));

        self::assertFalse($message->hasHeader('baz'));
        self::assertEquals(null, $message->getHeader('baz'));
        self::assertEquals('boom', $message->getHeader('baz', 'boom'));
    }

    public function testWithProperty()
    {
        $message = new Message('', ['foo' => 'bar']);
        $new = $message->withProperty('baz', 'qux');

        self::assertNotSame($message, $new, 'withProperty method should return a new copy of the Message');
        self::assertEquals(['foo' => 'bar', 'baz' => 'qux'], $new->getProperties());
    }

    public function testWithProperties()
    {
        $message = new Message('', ['foo' => 'bar']);
        $new = $message->withProperties(['baz' => 'qux', 'foo' => 'zoo']);

        self::assertNotSame($message, $new, 'withProperties method should return a new copy of the Message');
        self::assertEquals(['foo' => 'zoo', 'baz' => 'qux'], $new->getProperties());
    }

    public function testWithoutProperty()
    {
        $message = new Message('', ['foo' => 'bar', 'baz' => 'qux']);
        $new = $message->withoutProperty('foo');

        self::assertNotSame($message, $new, 'withoutProperty method should return a new copy of the Message');
        self::assertEquals(['baz' => 'qux'], $new->getProperties());
    }

    public function testWithHeader()
    {
        $message = new Message('', ['headers' => ['foo' => 'bar'], 'type' => 'text']);
        $new = $message->withHeader('baz', 'qux');

        self::assertNotSame($message, $new, 'withHeader method should return a new copy of the Message');
        self::assertEquals($message->getProperty('type'), $new->getProperty('type'));
        self::assertEquals(['foo' => 'bar', 'baz' => 'qux'], $new->getHeaders());
    }

    public function testWithHeaders()
    {
        $message = new Message('', ['headers' => ['foo' => 'bar'], 'type' => 'text']);
        $new = $message->withHeaders(['baz' => 'qux', 'foo' => 'zoo']);

        self::assertNotSame($message, $new, 'withHeaders method should return a new copy of the Message');
        self::assertEquals($message->getProperty('type'), $new->getProperty('type'));
        self::assertEquals(['foo' => 'zoo', 'baz' => 'qux'], $new->getHeaders());
    }

    public function testWithoutHeader()
    {
        $message = new Message('', ['headers' => ['foo' => 'bar', 'baz' => 'qux'], 'type' => 'text']);
        $new = $message->withoutHeader('foo');

        self::assertNotSame($message, $new, 'withoutHeader method should return a new copy of the Message');
        self::assertEquals($message->getProperty('type'), $new->getProperty('type'));
        self::assertEquals(['baz' => 'qux'], $new->getHeaders());
    }
}
