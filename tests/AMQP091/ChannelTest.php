<?php

namespace ButterAMQPTest\AMQP091;

use ButterAMQP\AMQP091\Channel;
use ButterAMQP\Confirm;
use ButterAMQP\AMQP091\Consumer;
use ButterAMQP\Delivery;
use ButterAMQP\Exception\TransactionNotSelectedException;
use ButterAMQP\AMQP091\Exchange;
use ButterAMQP\AMQP091\Framing\Content;
use ButterAMQP\AMQP091\Framing\Header;
use ButterAMQP\AMQP091\Framing\Method\BasicAck;
use ButterAMQP\AMQP091\Framing\Method\BasicCancel;
use ButterAMQP\AMQP091\Framing\Method\BasicCancelOk;
use ButterAMQP\AMQP091\Framing\Method\BasicConsumeOk;
use ButterAMQP\AMQP091\Framing\Method\BasicConsume;
use ButterAMQP\AMQP091\Framing\Method\BasicGet;
use ButterAMQP\AMQP091\Framing\Method\BasicGetEmpty;
use ButterAMQP\AMQP091\Framing\Method\BasicGetOk;
use ButterAMQP\AMQP091\Framing\Method\BasicNack;
use ButterAMQP\AMQP091\Framing\Method\BasicPublish;
use ButterAMQP\AMQP091\Framing\Method\BasicQos;
use ButterAMQP\AMQP091\Framing\Method\BasicQosOk;
use ButterAMQP\AMQP091\Framing\Method\BasicRecover;
use ButterAMQP\AMQP091\Framing\Method\BasicRecoverOk;
use ButterAMQP\AMQP091\Framing\Method\BasicReject;
use ButterAMQP\AMQP091\Framing\Method\ChannelClose;
use ButterAMQP\AMQP091\Framing\Method\ChannelCloseOk;
use ButterAMQP\AMQP091\Framing\Method\ChannelFlow;
use ButterAMQP\AMQP091\Framing\Method\ChannelFlowOk;
use ButterAMQP\AMQP091\Framing\Method\ChannelOpen;
use ButterAMQP\AMQP091\Framing\Method\ChannelOpenOk;
use ButterAMQP\AMQP091\Framing\Method\ConfirmSelect;
use ButterAMQP\AMQP091\Framing\Method\ConfirmSelectOk;
use ButterAMQP\AMQP091\Framing\Method\TxCommit;
use ButterAMQP\AMQP091\Framing\Method\TxCommitOk;
use ButterAMQP\AMQP091\Framing\Method\TxRollback;
use ButterAMQP\AMQP091\Framing\Method\TxRollbackOk;
use ButterAMQP\AMQP091\Framing\Method\TxSelect;
use ButterAMQP\AMQP091\Framing\Method\TxSelectOk;
use ButterAMQP\Message;
use ButterAMQP\AMQP091\Queue;
use ButterAMQP\Returned;
use ButterAMQP\AMQP091\WireInterface;
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
     * Channel should not send frame once connection already open.
     */
    public function testOpen()
    {
        $this->wire->expects(self::once())
            ->method('send')
            ->with(new ChannelOpen(51, ''));

        $this->wire->expects(self::once())
            ->method('wait')
            ->with(51, ChannelOpenOk::class);

        $this->channel->open();
        $this->channel->open();
    }

    /**
     * Channel should allow to activate and deactivate flow.
     * Channel status should be set based on reply from the server.
     */
    public function testFlow()
    {
        $this->wire->expects(self::once())
            ->method('send')
            ->with(new ChannelFlow(51, true));

        $this->wire->expects(self::once())
            ->method('wait')
            ->with(51, ChannelFlowOk::class)
            ->willReturn(new ChannelFlowOk(51, false));

        $this->channel->flow(true);

        self::assertEquals(
            Channel::STATUS_INACTIVE,
            $this->channel->getStatus(),
            'Channel status should be the same as returned by server'
        );
    }

    /**
     * Channel should fetch next frame from the wire while serving.
     */
    public function testServe()
    {
        $this->wire->expects(self::once())
            ->method('next')
            ->with(true);

        $this->channel->serve();
    }

    /**
     * Channel should send channel.close and wait for reply.
     */
    public function testClose()
    {
        $this->wire->expects(self::once())
            ->method('send')
            ->with(new ChannelClose(51, 0, '', 0, 0));

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
            ->with(new BasicQos(51, 1, 2, false));

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
            ->with(new BasicConsume(51, 0, 'butter', '', false, false, false, false, []));

        $this->wire->expects(self::once())
            ->method('wait')
            ->with(51, BasicConsumeOk::class)
            ->willReturn(new BasicConsumeOk(51, 'foo'));

        $consumer = $this->channel
            ->consume('butter', function () {
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
            ->with(new BasicConsume(51, 0, 'butter', 'foo', false, false, false, true, []));

        $this->wire->expects(self::never())
            ->method('wait');

        $consumer = $this->channel->consume(
            'butter',
            function () {
            },
            Consumer::FLAG_NO_WAIT,
            'foo'
        );

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
            ->with(self::isInstanceOf(BasicConsume::class));

        $this->wire->expects(self::never())
            ->method('wait');

        $consumer = $this->channel->consume(
            'butter',
            function () {
            },
            Consumer::FLAG_NO_WAIT
        );

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
            ->with(new BasicCancel(51, 'tag', false));

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
            ->with(new BasicCancel(51, 'tag', true));

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
            ->with(new BasicAck(51, 11, true));

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
            ->with(new BasicReject(51, 11, true));

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
            ->with(new BasicNack(51, 11, true, false));

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
            ->with(new BasicPublish(51, 0, 'foo', 'bar', false, false));

        $this->wire->expects(self::at(1))
            ->method('send')
            ->with(new Header(51, 60, 0, 6, ['delivery-mode' => 1]));

        $this->wire->expects(self::at(2))
            ->method('send')
            ->with(new Content(51, 'butter'));

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
            ->with(new BasicGet(51, 0, 'test', false));

        $this->wire->expects(self::atLeastOnce())
            ->method('wait')
            ->withConsecutive(
                [51, [BasicGetOk::class, BasicGetEmpty::class]],
                [51, Header::class],
                [51, Content::class],
                [51, Content::class]
            )
            ->willReturnOnConsecutiveCalls(
                new BasicGetOk(51, 1, false, 'inbox', 'test', 0),
                new Header(51, 60, 0, 6, []),
                new Content(51, 'foo'),
                new Content(51, 'bar')
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
            ->with(new BasicGet(51, 0, 'test', false));

        $this->wire->expects(self::once())
            ->method('wait')
            ->with(51, [BasicGetOk::class, BasicGetEmpty::class])
            ->willReturn(new BasicGetEmpty(51, 0));

        $this->channel->get('test', true);
    }

    /**
     * Channel should send basic.recover and wait for reply.
     */
    public function testRecover()
    {
        $this->wire->expects(self::once())
            ->method('send')
            ->with(new BasicRecover(51, true));

        $this->wire->expects(self::once())
            ->method('wait')
            ->with(51, BasicRecoverOk::class);

        $this->channel->recover(true);
    }

    /**
     * Channel should send confirm.select and wait for reply when choosing confirm mode.
     */
    public function testSelectConfirm()
    {
        $this->wire->expects(self::once())
            ->method('send')
            ->with(new ConfirmSelect(51, false));

        $this->wire->expects(self::once())
            ->method('wait')
            ->with(51, ConfirmSelectOk::class);

        $this->channel->selectConfirm(function () {
        });
    }

    /**
     * Channel should send confirm.select and not wait for reply when choosing confirm mode in no-wait mode.
     */
    public function testSelectConfirmNoWait()
    {
        $this->wire->expects(self::once())
            ->method('send')
            ->with(new ConfirmSelect(51, true));

        $this->wire->expects(self::never())
            ->method('wait');

        $this->channel->selectConfirm(
            function () {
            },
            true
        );
    }

    /**
     * Channel should send tx.select and wait for reply when choosing transactional mode.
     */
    public function testSelectTx()
    {
        $this->wire->expects(self::once())
            ->method('send')
            ->with(new TxSelect(51));

        $this->wire->expects(self::once())
            ->method('wait')
            ->with(51, TxSelectOk::class);

        $this->channel->selectTx();
    }

    /**
     * Channel should send tx.commit and wait for reply when committing transaction.
     */
    public function testTxCommit()
    {
        $this->wire->expects(self::exactly(2))
            ->method('send')
            ->withConsecutive(
                [new TxSelect(51)],
                [new TxCommit(51)]
            );

        $this->wire->expects(self::exactly(2))
            ->method('wait')
            ->withConsecutive(
                [51, TxSelectOk::class],
                [51, TxCommitOk::class]
            );

        $this->channel->selectTx()
            ->txCommit();
    }

    /**
     * Channel should throw an exception when committing transaction in non transactional mode.
     */
    public function testTxCommitThrowsException()
    {
        $this->expectException(TransactionNotSelectedException::class);

        $this->channel->txCommit();
    }

    /**
     * Channel should send tx.rollback and wait for reply when rolling back transaction.
     */
    public function testTxRollback()
    {
        $this->wire->expects(self::exactly(2))
            ->method('send')
            ->withConsecutive(
                [new TxSelect(51)],
                [new TxRollback(51)]
            );

        $this->wire->expects(self::exactly(2))
            ->method('wait')
            ->withConsecutive(
                [51, TxSelectOk::class],
                [51, TxRollbackOk::class]
            );

        $this->channel->selectTx()
            ->txRollback();
    }

    /**
     * Channel should throw an exception when rolling back transaction in non transactional mode.
     */
    public function testTxRollbackThrowsException()
    {
        $this->expectException(TransactionNotSelectedException::class);

        $this->channel->txRollback();
    }
}
