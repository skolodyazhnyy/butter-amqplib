<?php

namespace ButterAMQPTest;

use ButterAMQP\Exchange;
use ButterAMQP\Framing\Method\ExchangeBind;
use ButterAMQP\Framing\Method\ExchangeBindOk;
use ButterAMQP\Framing\Method\ExchangeDeclare;
use ButterAMQP\Framing\Method\ExchangeDeclareOk;
use ButterAMQP\Framing\Method\ExchangeDelete;
use ButterAMQP\Framing\Method\ExchangeDeleteOk;
use ButterAMQP\Framing\Method\ExchangeUnbind;
use ButterAMQP\Framing\Method\ExchangeUnbindOk;
use ButterAMQP\WireInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;

class ExchangeTest extends TestCase
{
    /**
     * @var WireInterface|Mock
     */
    private $wire;

    /**
     * @var int
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
        $this->wire = $this->createMock(WireInterface::class);
        $this->channel = 51;
        $this->exchange = new Exchange($this->wire, $this->channel, 'foo');
    }

    /**
     * Exchange should send exchange.declare frame.
     */
    public function testDeclare()
    {
        $this->wire->expects(self::once())
            ->method('send')
            ->with(new ExchangeDeclare($this->channel, 0, 'foo', 'fanout', true, true, false, false, true, ['foo' => 'bar']));

        $this->wire->expects(self::never())
            ->method('wait');

        $flags = Exchange::FLAG_DURABLE | Exchange::FLAG_PASSIVE | Exchange::FLAG_NO_WAIT;

        $this->exchange->define('fanout', $flags, ['foo' => 'bar']);
    }

    /**
     * Exchange should send exchange.declare and wait for reply.
     */
    public function testDeclareBlocking()
    {
        $this->wire->expects(self::once())
            ->method('send')
            ->with(new ExchangeDeclare($this->channel, 0, 'foo', 'fanout', true, true, false, false, false, ['foo' => 'bar']));

        $this->wire->expects(self::once())
            ->method('wait')
            ->with($this->channel, ExchangeDeclareOk::class);

        $flags = Exchange::FLAG_DURABLE | Exchange::FLAG_PASSIVE;

        $this->exchange->define('fanout', $flags, ['foo' => 'bar']);
    }

    /**
     * Exchange should send exchange.delete frame.
     */
    public function testDelete()
    {
        $this->wire->expects(self::once())
            ->method('send')
            ->with(new ExchangeDelete($this->channel, 0, 'foo', true, true));

        $this->wire->expects(self::never())
            ->method('wait');

        $this->exchange->delete(Exchange::FLAG_IF_UNUSED | Exchange::FLAG_NO_WAIT);
    }

    /**
     * Exchange should send exchange.delete and wait for reply.
     */
    public function testDeleteBlocking()
    {
        $this->wire->expects(self::once())
            ->method('send')
            ->with(new ExchangeDelete($this->channel, 0, 'foo', true, false));

        $this->wire->expects(self::once())
            ->method('wait')
            ->with($this->channel, ExchangeDeleteOk::class);

        $this->exchange->delete(Exchange::FLAG_IF_UNUSED);
    }

    /**
     * Exchange should send exchange.bind frame.
     */
    public function testBind()
    {
        $this->wire->expects(self::once())
            ->method('send')
            ->with(new ExchangeBind($this->channel, 0, 'bar', 'foo', 'baz', true, ['foo' => 'bar']));

        $this->wire->expects(self::never())
            ->method('wait');

        $this->exchange->bind('bar', 'baz', ['foo' => 'bar'], Exchange::FLAG_NO_WAIT);
    }

    /**
     * Exchange should send exchange.bind and wait for reply.
     */
    public function testBindBlocking()
    {
        $this->wire->expects(self::once())
            ->method('send')
            ->with(new ExchangeBind($this->channel, 0, 'bar', 'foo', 'baz', false, ['foo' => 'bar']));

        $this->wire->expects(self::once())
            ->method('wait')
            ->with($this->channel, ExchangeBindOk::class);

        $this->exchange->bind('bar', 'baz', ['foo' => 'bar']);
    }

    /**
     * Exchange should send exchange.unbind frame.
     */
    public function testUnbind()
    {
        $this->wire->expects(self::once())
            ->method('send')
            ->with(new ExchangeBind($this->channel, 0, 'bar', 'foo', 'baz', true, ['foo' => 'bar']));

        $this->wire->expects(self::never())
            ->method('wait');

        $this->exchange->bind('bar', 'baz', ['foo' => 'bar'], Exchange::FLAG_NO_WAIT);
    }

    /**
     * Exchange should send exchange.unbind and wait for reply.
     */
    public function testUnbindBlocking()
    {
        $this->wire->expects(self::once())
            ->method('send')
            ->with(new ExchangeUnbind($this->channel, 0, 'bar', 'foo', 'baz', false, ['foo' => 'bar']));

        $this->wire->expects(self::once())
            ->method('wait')
            ->with($this->channel, ExchangeUnbindOk::class);

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
