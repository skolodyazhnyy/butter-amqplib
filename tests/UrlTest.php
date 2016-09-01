<?php

namespace AMQLibTest;

use AMQLib\Url;
use PHPUnit\Framework\TestCase;

class UrlTest extends TestCase
{
    public function testParse()
    {
        $url = Url::parse('amqps://foo:bar@amqp.example.org:6001/baz');

        self::assertEquals('amqps', $url->getScheme());
        self::assertEquals('amqp.example.org', $url->getHost());
        self::assertEquals(6001, $url->getPort());
        self::assertEquals('foo', $url->getUser());
        self::assertEquals('bar', $url->getPass());
        self::assertEquals('baz', $url->getVhost());
    }

    public function testParseInvalidUrl()
    {
        $this->expectException(\InvalidArgumentException::class);

        $url = Url::parse('//:amqp');

        self::assertEquals('amqps', $url->getScheme());
        self::assertEquals('amqp.example.org', $url->getHost());
        self::assertEquals(6001, $url->getPort());
        self::assertEquals('foo', $url->getUser());
        self::assertEquals('bar', $url->getPass());
        self::assertEquals('baz', $url->getVhost());
    }

    public function testCompose()
    {
        $url = new Url('amqps', 'amqp.example.org', 6001, 'foo', 'bar', 'baz');

        self::assertEquals('amqps://foo:******@amqp.example.org:6001/baz', $url->compose(true));
        self::assertEquals('amqps://foo:bar@amqp.example.org:6001/baz', $url->compose());
        self::assertEquals('amqps://foo:bar@amqp.example.org:6001/baz', (string) $url);
    }
}
