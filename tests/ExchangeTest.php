<?php

namespace AMQLibTest;

use AMQLib\Exchange;
use AMQLib\FrameChannelInterface;
use AMQLib\Framing\Method\ExchangeBind;
use AMQLib\Framing\Method\ExchangeBindOk;
use AMQLib\Framing\Method\ExchangeDeclare;
use AMQLib\Framing\Method\ExchangeDeclareOk;
use AMQLib\Framing\Method\ExchangeDelete;
use AMQLib\Framing\Method\ExchangeDeleteOk;
use AMQLib\Framing\Method\ExchangeUnbind;
use AMQLib\Framing\Method\ExchangeUnbindOk;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;

class ExchangeTest extends TestCase
{
    /**
     * @var FrameChannelInterface|Mock
     */
    private $channel;

    /**
     * @var Exchange
     */
    private $exchange;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->channel = $this->createMock(FrameChannelInterface::class);
        $this->exchange = new Exchange($this->channel, 'foo');
    }

    /**
     * Exchange should send exchange.declare frame.
     */
    public function testDeclare()
    {
        $this->channel->expects(self::once())
            ->method('send')
            ->with(new ExchangeDeclare(0, 'foo', 'fanout', true, true, false, false, true, ['foo' => 'bar']));

        $this->channel->expects(self::never())
            ->method('wait');

        $flags = Exchange::FLAG_DURABLE | Exchange::FLAG_PASSIVE | Exchange::FLAG_NO_WAIT;

        $this->exchange->define('fanout', $flags, ['foo' => 'bar']);
    }

    /**
     * Exchange should send exchange.declare and wait for reply.
     */
    public function testDeclareBlocking()
    {
        $this->channel->expects(self::once())
            ->method('send')
            ->with(new ExchangeDeclare(0, 'foo', 'fanout', true, true, false, false, false, ['foo' => 'bar']));

        $this->channel->expects(self::once())
            ->method('wait')
            ->with(ExchangeDeclareOk::class);

        $flags = Exchange::FLAG_DURABLE | Exchange::FLAG_PASSIVE;

        $this->exchange->define('fanout', $flags, ['foo' => 'bar']);
    }

    /**
     * Exchange should send exchange.delete frame.
     */
    public function testDelete()
    {
        $this->channel->expects(self::once())
            ->method('send')
            ->with(new ExchangeDelete(0, 'foo', true, true));

        $this->channel->expects(self::never())
            ->method('wait');

        $this->exchange->delete(Exchange::FLAG_IF_UNUSED | Exchange::FLAG_NO_WAIT);
    }

    /**
     * Exchange should send exchange.delete and wait for reply.
     */
    public function testDeleteBlocking()
    {
        $this->channel->expects(self::once())
            ->method('send')
            ->with(new ExchangeDelete(0, 'foo', true, false));

        $this->channel->expects(self::once())
            ->method('wait')
            ->with(ExchangeDeleteOk::class);

        $this->exchange->delete(Exchange::FLAG_IF_UNUSED);
    }

    /**
     * Exchange should send exchange.bind frame.
     */
    public function testBind()
    {
        $this->channel->expects(self::once())
            ->method('send')
            ->with(new ExchangeBind(0, 'bar', 'foo', 'baz', true, ['foo' => 'bar']));

        $this->channel->expects(self::never())
            ->method('wait');

        $this->exchange->bind('bar', 'baz', ['foo' => 'bar'], Exchange::FLAG_NO_WAIT);
    }

    /**
     * Exchange should send exchange.bind and wait for reply.
     */
    public function testBindBlocking()
    {
        $this->channel->expects(self::once())
            ->method('send')
            ->with(new ExchangeBind(0, 'bar', 'foo', 'baz', false, ['foo' => 'bar']));

        $this->channel->expects(self::once())
            ->method('wait')
            ->with(ExchangeBindOk::class);

        $this->exchange->bind('bar', 'baz', ['foo' => 'bar']);
    }

    /**
     * Exchange should send exchange.unbind frame.
     */
    public function testUnbind()
    {
        $this->channel->expects(self::once())
            ->method('send')
            ->with(new ExchangeBind(0, 'bar', 'foo', 'baz', true, ['foo' => 'bar']));

        $this->channel->expects(self::never())
            ->method('wait');

        $this->exchange->bind('bar', 'baz', ['foo' => 'bar'], Exchange::FLAG_NO_WAIT);
    }

    /**
     * Exchange should send exchange.unbind and wait for reply.
     */
    public function testUnbindBlocking()
    {
        $this->channel->expects(self::once())
            ->method('send')
            ->with(new ExchangeUnbind(0, 'bar', 'foo', 'baz', false, ['foo' => 'bar']));

        $this->channel->expects(self::once())
            ->method('wait')
            ->with(ExchangeUnbindOk::class);

        $this->exchange->unbind('bar', 'baz', ['foo' => 'bar']);
    }

    /**
     * Exchange should know its name.
     */
    public function testName()
    {
        self::assertEquals('foo', $this->exchange->name());
    }

    /**
     * Exchange should cast to string as tag name.
     */
    public function testToString()
    {
        self::assertEquals('foo', $this->exchange);
    }
}
