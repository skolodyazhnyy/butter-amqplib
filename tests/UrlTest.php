<?php

namespace ButterAMQPTest;

use ButterAMQP\Url;
use PHPUnit\Framework\TestCase;

class UrlTest extends TestCase
{
    /**
     * Create URL from a string.
     */
    public function testParse()
    {
        $url = Url::parse('amqps://foo:bar@amqp.example.org:6001/baz?far=boom');

        self::assertEquals('amqps', $url->getScheme());
        self::assertEquals('amqp.example.org', $url->getHost());
        self::assertEquals(6001, $url->getPort());
        self::assertEquals('foo', $url->getUser());
        self::assertEquals('bar', $url->getPassword());
        self::assertEquals('baz', $url->getVhost());
        self::assertEquals(['far' => 'boom'], $url->getQuery());
    }

    /**
     * Unsecure default port should be 5672.
     * Secure default port should be 5671.
     */
    public function testDefaultPort()
    {
        $unsecureUrl = new Url();
        $secureUrl = new Url('amqps');

        self::assertEquals(5672, $unsecureUrl->getPort());
        self::assertEquals(5671, $secureUrl->getPort());
    }

    /**
     * An exception should be thrown if URL string is invalid.
     */
    public function testParseInvalidUrl()
    {
        $this->expectException(\InvalidArgumentException::class);

        $url = Url::parse('//:amqp');

        self::assertEquals('amqps', $url->getScheme());
        self::assertEquals('amqp.example.org', $url->getHost());
        self::assertEquals(6001, $url->getPort());
        self::assertEquals('foo', $url->getUser());
        self::assertEquals('bar', $url->getPassword());
        self::assertEquals('baz', $url->getVhost());
    }

    /**
     * Create URL from an array.
     */
    public function testImport()
    {
        $url = Url::import([
            'scheme' => 'amqps',
            'host' => 'amqp.example.org',
            'port' => 6001,
            'user' => 'foo',
            'password' => 'bar',
            'vhost' => 'baz',
            'parameters' => [
                'far' => 'boom',
            ],
        ]);

        self::assertEquals('amqps', $url->getScheme());
        self::assertEquals('amqp.example.org', $url->getHost());
        self::assertEquals(6001, $url->getPort());
        self::assertEquals('foo', $url->getUser());
        self::assertEquals('bar', $url->getPassword());
        self::assertEquals('baz', $url->getVhost());
        self::assertEquals(['far' => 'boom'], $url->getQuery());
    }

    /**
     * Export URL into an array.
     */
    public function testExport()
    {
        $url = Url::parse('amqps://foo:bar@amqp.example.org:6001/baz?far=boom');

        $expected = [
            'scheme' => 'amqps',
            'host' => 'amqp.example.org',
            'port' => 6001,
            'user' => 'foo',
            'password' => 'bar',
            'vhost' => 'baz',
            'parameters' => [
                'far' => 'boom',
            ],
        ];

        self::assertEquals($expected, $url->export());
    }

    /**
     * Compose URL into a string.
     */
    public function testCompose()
    {
        $url = new Url('amqps', 'amqp.example.org', 6001, 'foo', 'bar', 'baz', ['far' => 'boom']);

        self::assertEquals('amqps://foo:******@amqp.example.org:6001/baz?far=boom', $url->compose(true));
        self::assertEquals('amqps://foo:bar@amqp.example.org:6001/baz?far=boom', $url->compose(false));
        self::assertEquals('amqps://foo:******@amqp.example.org:6001/baz?far=boom', $url->compose());
        self::assertEquals('amqps://foo:******@amqp.example.org:6001/baz?far=boom', (string) $url);
    }
}
