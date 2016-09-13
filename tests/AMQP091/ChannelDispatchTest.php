<?php

namespace ButterAMQPTest\AMQP091;

use ButterAMQP\AMQP091\Channel;
use ButterAMQP\Confirm;
use ButterAMQP\AMQP091\Consumer;
use ButterAMQP\Delivery;
use ButterAMQP\Exception\ChannelException;
use ButterAMQP\Exception\NoReturnException;
use ButterAMQP\Exception\UnknownConsumerTagException;
use ButterAMQP\AMQP091\Framing\Content;
use ButterAMQP\AMQP091\Framing\Header;
use ButterAMQP\AMQP091\Framing\Method\BasicAck;
use ButterAMQP\AMQP091\Framing\Method\BasicCancel;
use ButterAMQP\AMQP091\Framing\Method\BasicCancelOk;
use ButterAMQP\AMQP091\Framing\Method\BasicConsumeOk;
use ButterAMQP\AMQP091\Framing\Method\BasicConsume;
use ButterAMQP\AMQP091\Framing\Method\BasicDeliver;
use ButterAMQP\AMQP091\Framing\Method\BasicNack;
use ButterAMQP\AMQP091\Framing\Method\BasicReturn;
use ButterAMQP\AMQP091\Framing\Method\ChannelClose;
use ButterAMQP\AMQP091\Framing\Method\ChannelCloseOk;
use ButterAMQP\AMQP091\Framing\Method\ChannelFlow;
use ButterAMQP\AMQP091\Framing\Method\ChannelFlowOk;
use ButterAMQP\Returned;
use ButterAMQP\AMQP091\WireInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;

class ChannelDispatchTest extends TestCase
{
    /**
     * @var WireInterface|Mock
     */
    private $wire;

    /**
     * @var Channel
     */
    private $channel;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->wire = $this->createMock(WireInterface::class);
        $this->channel = new Channel($this->wire, 51);
    }

    /**
     * Channel should change status when channel.close is received.
     */
    public function testDispatchChannelClose()
    {
        $this->expectException(ChannelException::class);

        $this->wire->expects(self::once())
            ->method('send')
            ->with(new ChannelCloseOk(51));

        $this->channel->dispatch(new ChannelClose(51, 404, 'Not found', 0, 0));

        self::assertEquals(Channel::STATUS_CLOSED, $this->channel->getStatus());
    }

    /**
     * Channel should change status when channel.flow is received.
     */
    public function testDispatchChannelFlow()
    {
        $this->wire->expects(self::once())
            ->method('send')
            ->with(new ChannelFlowOk(51, true));

        $this->channel->dispatch(new ChannelFlow(51, true));

        self::assertEquals(Channel::STATUS_READY, $this->channel->getStatus());
    }

    /**
     * Channel should call consumer callback when basic.delivery is received.
     */
    public function testDispatchBasicDeliver()
    {
        $consumer = $this->getMockBuilder(\stdClass::class)->setMethods(['__invoke'])->getMock();
        $consumer->expects(self::once())
            ->method('__invoke')
            ->willReturnCallback(function (Delivery $delivery) {
                self::assertEquals('abcdef', $delivery->getBody());
                self::assertEquals(['delivery-mode' => 2], $delivery->getProperties());
                self::assertEquals('bar', $delivery->getExchange());
                self::assertEquals('baz', $delivery->getRoutingKey());
            });

        $this->wire->expects(self::exactly(4))
            ->method('wait')
            ->willReturnOnConsecutiveCalls(
                new BasicConsumeOk(51, 'foo'),
                new Header(51, 60, 0, 6, ['delivery-mode' => 2]),
                new Content(51, 'abc'),
                new Content(51, 'def')
            );

        $this->channel->consume('', $consumer, 0, 'foo');

        $this->channel->dispatch(new BasicDeliver(51, 'foo', 77, false, 'bar', 'baz'));
    }

    /**
     * Channel should throw an exception when basic.delivery for unknown consumer is received.
     */
    public function testDispatchBasicDeliverForUnknownConsumer()
    {
        $this->expectException(UnknownConsumerTagException::class);

        $this->wire->expects(self::atLeastOnce())
            ->method('wait')
            ->willReturnOnConsecutiveCalls(
                new Header(51, 60, 0, 6, ['delivery-mode' => 2]),
                new Content(51, 'abc'),
                new Content(51, 'def')
            );

        $this->channel->dispatch(new BasicDeliver(51, 'foo', 77, false, 'bar', 'baz'));
    }

    /**
     * Channel should call return callback when basic.return is received.
     */
    public function testDispatchBasicReturn()
    {
        $callable = $this->getCallableMock();
        $callable->expects(self::once())
            ->method('__invoke')
            ->with(self::isInstanceOf(Returned::class));

        $this->channel->onReturn($callable);

        $this->wire->expects(self::atLeastOnce())
            ->method('wait')
            ->willReturnOnConsecutiveCalls(
                new Header(51, 60, 0, 6, ['delivery-mode' => 2]),
                new Content(51, 'abc'),
                new Content(51, 'def')
            );

        $this->channel->dispatch(new BasicReturn(51, 0, '', '', ''));
    }

    /**
     * Channel should throw an exception when basic.return is received but return callback is not set.
     */
    public function testDispatchBasicReturnWithoutReturnCallable()
    {
        $this->expectException(NoReturnException::class);

        $this->wire->expects(self::atLeastOnce())
            ->method('wait')
            ->willReturnOnConsecutiveCalls(
                new Header(51, 60, 0, 6, ['delivery-mode' => 2]),
                new Content(51, 'abc'),
                new Content(51, 'def')
            );

        $this->channel->dispatch(new BasicReturn(51, 0, '', '', ''));
    }

    /**
     * Channel should call confirm callback when basic.ack is received.
     */
    public function testDispatchBasicAck()
    {
        $callable = $this->getCallableMock();
        $callable->expects(self::once())
            ->method('__invoke')
            ->with(new Confirm(true, 2, true));

        $this->channel->selectConfirm($callable);

        $this->channel->dispatch(new BasicAck(51, 2, true));
    }

    /**
     * Channel should throw an exception when basic.ack is received but confirm callback is not set.
     */
    public function testDispatchBasicAckWithoutConfirmCallable()
    {
        $this->expectException(\RuntimeException::class);

        $this->channel->dispatch(new BasicAck(2, true, true));
    }

    /**
     * Channel should call confirm callback when basic.nack is received.
     */
    public function testDispatchBasicNack()
    {
        $callable = $this->getCallableMock();
        $callable->expects(self::once())
            ->method('__invoke')
            ->with(new Confirm(false, 2, true));

        $this->channel->selectConfirm($callable);

        $this->channel->dispatch(new BasicNack(51, 2, true, true));
    }

    /**
     * Channel should throw an exception when basic.nack is received but confirm callback is not set.
     */
    public function testDispatchBasicNackWithoutConfirmCallable()
    {
        $this->expectException(\RuntimeException::class);

        $this->channel->dispatch(new BasicNack(51, 2, true, true));
    }

    /**
     * Channel should remove consumer when basic.cancel is received and send reply.
     */
    public function testDispatchBasicCancel()
    {
        $this->wire->expects(self::exactly(2))
            ->method('send')
            ->withConsecutive(
                [self::isInstanceOf(BasicConsume::class)],
                [new BasicCancelOk(51, 'baz')]
            );

        $this->channel->consume('foo', $this->getCallableMock(), Consumer::FLAG_NO_WAIT, 'baz');

        self::assertTrue($this->channel->hasConsumer('baz'));

        $this->channel->dispatch(new BasicCancel(51, 'baz', false));

        self::assertFalse($this->channel->hasConsumer('baz'));
    }

    /**
     * Channel should not send reply when basic.cancel is received with no wait flag.
     */
    public function testDispatchBasicCancelNoWait()
    {
        $this->wire->expects(self::exactly(1))
            ->method('send')
            ->with(self::isInstanceOf(BasicConsume::class));

        $this->channel->consume('foo', $this->getCallableMock(), Consumer::FLAG_NO_WAIT, 'baz');

        self::assertTrue($this->channel->hasConsumer('baz'));

        $this->channel->dispatch(new BasicCancel(51, 'baz', true));

        self::assertFalse($this->channel->hasConsumer('baz'));
    }

    /**
     * @return Mock|callable
     */
    private function getCallableMock()
    {
        return $this->getMockBuilder(\stdClass::class)
            ->setMethods(['__invoke'])
            ->getMock();
    }
}
