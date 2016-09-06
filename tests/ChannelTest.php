<?php

namespace ButterAMQPTest;

use ButterAMQP\Channel;
use ButterAMQP\Confirm;
use ButterAMQP\Consumer;
use ButterAMQP\Delivery;
use ButterAMQP\Exception\ChannelException;
use ButterAMQP\Exception\NoReturnException;
use ButterAMQP\Exception\UnknownConsumerTagException;
use ButterAMQP\Exchange;
use ButterAMQP\Framing\Content;
use ButterAMQP\Framing\Header;
use ButterAMQP\Framing\Method\BasicAck;
use ButterAMQP\Framing\Method\BasicCancel;
use ButterAMQP\Framing\Method\BasicCancelOk;
use ButterAMQP\Framing\Method\BasicConsumeOk;
use ButterAMQP\Framing\Method\BasicConsume;
use ButterAMQP\Framing\Method\BasicDeliver;
use ButterAMQP\Framing\Method\BasicGet;
use ButterAMQP\Framing\Method\BasicGetEmpty;
use ButterAMQP\Framing\Method\BasicGetOk;
use ButterAMQP\Framing\Method\BasicNack;
use ButterAMQP\Framing\Method\BasicPublish;
use ButterAMQP\Framing\Method\BasicQos;
use ButterAMQP\Framing\Method\BasicQosOk;
use ButterAMQP\Framing\Method\BasicRecover;
use ButterAMQP\Framing\Method\BasicRecoverOk;
use ButterAMQP\Framing\Method\BasicReject;
use ButterAMQP\Framing\Method\BasicReturn;
use ButterAMQP\Framing\Method\ChannelClose;
use ButterAMQP\Framing\Method\ChannelCloseOk;
use ButterAMQP\Framing\Method\ChannelFlow;
use ButterAMQP\Framing\Method\ChannelFlowOk;
use ButterAMQP\Framing\Method\ChannelOpen;
use ButterAMQP\Framing\Method\ChannelOpenOk;
use ButterAMQP\Framing\Method\ConfirmSelect;
use ButterAMQP\Framing\Method\ConfirmSelectOk;
use ButterAMQP\Message;
use ButterAMQP\Queue;
use ButterAMQP\Returned;
use ButterAMQP\WireInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;

class ChannelTest extends TestCase
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
     * Channel should send channel.open frame and wait for reply.
     * Channel should not send frame more than once.
     */
    public function testOpen()
    {
        $this->wire->expects(self::once())
            ->method('send')
            ->with(51, new ChannelOpen(''));

        $this->wire->expects(self::once())
            ->method('wait')
            ->with(51, ChannelOpenOk::class);

        $this->channel->open();
        $this->channel->open();
    }

    /**
     * Channel should allow to activate and deactivate flow.
     * Channel status should be set based on reply from the server, not request.
     */
    public function testFlow()
    {
        $this->wire->expects(self::once())
            ->method('send')
            ->with(51, new ChannelFlow(true));

        $this->wire->expects(self::once())
            ->method('wait')
            ->with(51, ChannelFlowOk::class)
            ->willReturn(new ChannelFlowOk(false));

        $this->channel->flow(true);

        self::assertEquals(
            Channel::STATUS_INACTIVE,
            $this->channel->getStatus(),
            'Channel status should be the same as returned by server'
        );
    }

    /**
     * Channel should send channel.close and wait for reply.
     */
    public function testClose()
    {
        $this->wire->expects(self::once())
            ->method('send')
            ->with(51, new ChannelClose(0, '', 0, 0));

        $this->wire->expects(self::once())
            ->method('wait')
            ->with(51, ChannelCloseOk::class);

        $this->channel->close();

        self::assertEquals(Channel::STATUS_CLOSED, $this->channel->getStatus());
    }

    /**
     * Channel should send basic.qos and wait for reply.
     */
    public function testQos()
    {
        $this->wire->expects(self::once())
            ->method('send')
            ->with(51, new BasicQos(1, 2, false));

        $this->wire->expects(self::once())
            ->method('wait')
            ->with(51, BasicQosOk::class);

        $this->channel->qos(1, 2, false);
    }

    /**
     * Channel should provide exchange interface.
     */
    public function testExchange()
    {
        $exchange = $this->channel->exchange('butter');

        self::assertInstanceOf(Exchange::class, $exchange);
        self::assertEquals('butter', $exchange->name());
    }

    /**
     * Channel should provide queue interface.
     */
    public function testQueue()
    {
        $queue = $this->channel->queue('butter');

        self::assertInstanceOf(Queue::class, $queue);
        self::assertEquals('butter', $queue->name());
    }

    /**
     * Channel should send basic.consume and wait for reply.
     * Channel should remember consumer callback and its tag so when message arrives it can be processed.
     */
    public function testConsume()
    {
        $this->wire->expects(self::once())
            ->method('send')
            ->with(51, new BasicConsume(0, 'butter', '', false, false, false, false, []));

        $this->wire->expects(self::once())
            ->method('wait')
            ->with(51, BasicConsumeOk::class)
            ->willReturn(new BasicConsumeOk('foo'));

        $consumer = $this->channel->consume('butter', function () {
        });

        self::assertInstanceOf(Consumer::class, $consumer);
        self::assertEquals('foo', $consumer->tag());

        self::assertTrue($this->channel->hasConsumer('foo'));
        self::assertEquals(['foo'], $this->channel->getConsumerTags());
    }

    /**
     * Channel should send basic.consume with no wait.
     */
    public function testConsumeNoWait()
    {
        $this->wire->expects(self::once())
            ->method('send')
            ->with(51, new BasicConsume(0, 'butter', 'foo', false, false, false, true, []));

        $this->wire->expects(self::never())
            ->method('wait');

        $consumer = $this->channel->consume('butter', function () {
        }, Consumer::FLAG_NO_WAIT, 'foo');

        self::assertInstanceOf(Consumer::class, $consumer);
        self::assertEquals('foo', $consumer->tag());

        self::assertTrue($this->channel->hasConsumer('foo'));
        self::assertEquals(['foo'], $this->channel->getConsumerTags());
    }

    /**
     * Channel should generate a random tag if consuming called with no tag and no wait.
     */
    public function testConsumeNoWaitNoTag()
    {
        $this->wire->expects(self::once())
            ->method('send')
            ->with(51, self::isInstanceOf(BasicConsume::class));

        $this->wire->expects(self::never())
            ->method('wait');

        $consumer = $this->channel->consume('butter', function () {
        }, Consumer::FLAG_NO_WAIT);
        $tag = $consumer->tag();

        self::assertInstanceOf(Consumer::class, $consumer);
        self::assertNotEmpty($tag);

        self::assertTrue($this->channel->hasConsumer($tag));
        self::assertEquals([$tag], $this->channel->getConsumerTags());
    }

    /**
     * Channel should send basic.cancel and wait for reply.
     */
    public function testCancel()
    {
        $this->wire->expects(self::once())
            ->method('send')
            ->with(51, new BasicCancel('tag', false));

        $this->wire->expects(self::once())
            ->method('wait')
            ->with(51, BasicCancelOk::class);

        $this->channel->cancel('tag');
    }

    /**
     * Channel should send basic.cancel with no wait.
     */
    public function testCancelNoWait()
    {
        $this->wire->expects(self::once())
            ->method('send')
            ->with(51, new BasicCancel('tag', true));

        $this->wire->expects(self::never())
            ->method('wait');

        $this->channel->cancel('tag', Consumer::FLAG_NO_WAIT);
    }

    /**
     * Channel should send basic.ack.
     */
    public function testAck()
    {
        $this->wire->expects(self::once())
            ->method('send')
            ->with(51, new BasicAck(11, true));

        $this->wire->expects(self::never())
            ->method('wait');

        $this->channel->ack(11, true);
    }

    /**
     * Channel should send basic.reject when rejecting a single delivery.
     */
    public function testReject()
    {
        $this->wire->expects(self::once())
            ->method('send')
            ->with(51, new BasicReject(11, true));

        $this->wire->expects(self::never())
            ->method('wait');

        $this->channel->reject(11, true);
    }

    /**
     * Channel should send basic.nack when rejecting multiple deliveries.
     */
    public function testNack()
    {
        $this->wire->expects(self::once())
            ->method('send')
            ->with(51, new BasicNack(11, true, false));

        $this->wire->expects(self::never())
            ->method('wait');

        $this->channel->reject(11, false, true);
    }

    /**
     * Channel should send basic.publish.
     */
    public function testPublish()
    {
        $this->wire->expects(self::at(0))
            ->method('send')
            ->with(51, new BasicPublish(0, 'foo', 'bar', false, false));

        $this->wire->expects(self::at(1))
            ->method('send')
            ->with(51, new Header(60, 0, 6, ['delivery-mode' => 1]));

        $this->wire->expects(self::at(2))
            ->method('send')
            ->with(51, new Content('butter'));

        $this->wire->expects(self::never())
            ->method('wait');

        $this->channel->publish(new Message('butter', ['delivery-mode' => 1]), 'foo', 'bar');
    }

    /**
     * Channel should send basic.get when fetching a message and collect message when basic.get-ok is received.
     */
    public function testGet()
    {
        $this->wire->expects(self::once())
            ->method('send')
            ->with(51, new BasicGet(0, 'test', false));

        $this->wire->expects(self::atLeastOnce())
            ->method('wait')
            ->withConsecutive(
                [51, [BasicGetOk::class, BasicGetEmpty::class]],
                [51, Header::class],
                [51, Content::class],
                [51, Content::class]
            )
            ->willReturnOnConsecutiveCalls(
                new BasicGetOk(1, false, 'inbox', 'test', 0),
                new Header(60, 0, 6, []),
                new Content('foo'),
                new Content('bar')
            );

        $this->channel->get('test', true);
    }

    /**
     * Channel should send basic.get when fetching a message and return null when basic.get-empty is received.
     */
    public function testGetEmpty()
    {
        $this->wire->expects(self::once())
            ->method('send')
            ->with(51, new BasicGet(0, 'test', false));

        $this->wire->expects(self::once())
            ->method('wait')
            ->with(51, [BasicGetOk::class, BasicGetEmpty::class])
            ->willReturn(new BasicGetEmpty(0));

        $this->channel->get('test', true);
    }

    /**
     * Channel should send basic.recover and wait for reply.
     */
    public function testRecover()
    {
        $this->wire->expects(self::once())
            ->method('send')
            ->with(51, new BasicRecover(true));

        $this->wire->expects(self::once())
            ->method('wait')
            ->with(51, BasicRecoverOk::class);

        $this->channel->recover(true);
    }

    public function testConfirmMode()
    {
        $this->wire->expects(self::once())
            ->method('send')
            ->with(51, new ConfirmSelect(false));

        $this->wire->expects(self::once())
            ->method('wait')
            ->with(51, ConfirmSelectOk::class);

        $this->channel->onConfirm(function () {
        });
    }

    public function testConfirmModeNoWait()
    {
        $this->wire->expects(self::once())
            ->method('send')
            ->with(51, new ConfirmSelect(true));

        $this->wire->expects(self::never())
            ->method('wait');

        $this->channel->onConfirm(function () {
        }, true);
    }

    public function testDispatchChannelClose()
    {
        $this->expectException(ChannelException::class);

        $this->wire->expects(self::once())
            ->method('send')
            ->with(51, new ChannelCloseOk());

        $this->channel->dispatch(new ChannelClose(404, 'Not found', 0, 0));

        self::assertEquals(Channel::STATUS_CLOSED, $this->channel->getStatus());
    }

    public function testDispatchChannelFlow()
    {
        $this->wire->expects(self::once())
            ->method('send')
            ->with(51, new ChannelFlowOk(true));

        $this->channel->dispatch(new ChannelFlow(true));

        self::assertEquals(Channel::STATUS_READY, $this->channel->getStatus());
    }

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
                new BasicConsumeOk('foo'),
                new Header(60, 0, 6, ['delivery-mode' => 2]),
                new Content('abc'),
                new Content('def')
            );

        $this->channel->consume('', $consumer, 0, 'foo');

        $this->channel->dispatch(new BasicDeliver('foo', 77, false, 'bar', 'baz'));
    }

    public function testDispatchBasicDeliverForUnknownConsumer()
    {
        $this->expectException(UnknownConsumerTagException::class);

        $this->wire->expects(self::atLeastOnce())
            ->method('wait')
            ->willReturnOnConsecutiveCalls(
                new Header(60, 0, 6, ['delivery-mode' => 2]),
                new Content('abc'),
                new Content('def')
            );

        $this->channel->dispatch(new BasicDeliver('foo', 77, false, 'bar', 'baz'));
    }

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
                new Header(60, 0, 6, ['delivery-mode' => 2]),
                new Content('abc'),
                new Content('def')
            );

        $this->channel->dispatch(new BasicReturn(0, '', '', ''));
    }

    public function testDispatchBasicReturnWithoutReturnCallable()
    {
        $this->expectException(NoReturnException::class);

        $this->wire->expects(self::atLeastOnce())
            ->method('wait')
            ->willReturnOnConsecutiveCalls(
                new Header(60, 0, 6, ['delivery-mode' => 2]),
                new Content('abc'),
                new Content('def')
            );

        $this->channel->dispatch(new BasicReturn(0, '', '', ''));
    }

    public function testDispatchBasicAck()
    {
        $callable = $this->getCallableMock();
        $callable->expects(self::once())
            ->method('__invoke')
            ->with(new Confirm(true, 2, true));

        $this->channel->onConfirm($callable);

        $this->channel->dispatch(new BasicAck(2, true));
    }

    public function testDispatchBasicAckWithoutConfirmCallable()
    {
        $this->expectException(\RuntimeException::class);

        $this->channel->dispatch(new BasicAck(2, true, true));
    }

    public function testDispatchBasicNack()
    {
        $callable = $this->getCallableMock();
        $callable->expects(self::once())
            ->method('__invoke')
            ->with(new Confirm(false, 2, true));

        $this->channel->onConfirm($callable);

        $this->channel->dispatch(new BasicNack(2, true, true));
    }

    public function testDispatchBasicNackWithoutConfirmCallable()
    {
        $this->expectException(\RuntimeException::class);

        $this->channel->dispatch(new BasicNack(2, true, true));
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
