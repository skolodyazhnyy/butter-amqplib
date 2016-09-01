<?php

namespace AMQLibTest;

use AMQLib\Framing\Method\QueuePurge;
use AMQLib\Framing\Method\QueuePurgeOk;
use AMQLib\Queue;
use AMQLib\FrameChannelInterface;
use AMQLib\Framing\Method\QueueBind;
use AMQLib\Framing\Method\QueueBindOk;
use AMQLib\Framing\Method\QueueDeclare;
use AMQLib\Framing\Method\QueueDeclareOk;
use AMQLib\Framing\Method\QueueDelete;
use AMQLib\Framing\Method\QueueDeleteOk;
use AMQLib\Framing\Method\QueueUnbind;
use AMQLib\Framing\Method\QueueUnbindOk;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;

class QueueTest extends TestCase
{
    /**
     * @var FrameChannelInterface|Mock
     */
    private $channel;

    /**
     * @var Queue
     */
    private $queue;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->channel = $this->createMock(FrameChannelInterface::class);
        $this->queue = new Queue($this->channel, 'foo');
    }

    /**
     * Queue should send queue.declare frame.
     */
    public function testDeclare()
    {
        $this->channel->expects(self::once())
            ->method('send')
            ->with(new QueueDeclare(0, 'foo', true, true, false, false, true, ['foo' => 'bar']));

        $this->channel->expects(self::never())
            ->method('wait');

        $flags = Queue::FLAG_DURABLE | Queue::FLAG_PASSIVE | Queue::FLAG_NO_WAIT;

        $this->queue->define($flags, ['foo' => 'bar']);
    }

    /**
     * Queue should send queue.declare and wait for reply.
     */
    public function testDeclareBlocking()
    {
        $this->channel->expects(self::once())
            ->method('send')
            ->with(new QueueDeclare(0, 'foo', true, true, false, false, false, ['foo' => 'bar']));

        $this->channel->expects(self::once())
            ->method('wait')
            ->with(QueueDeclareOk::class)
            ->willReturn(new QueueDeclareOk('zoo', 59, 41));

        $flags = Queue::FLAG_DURABLE | Queue::FLAG_PASSIVE;

        $this->queue->define($flags, ['foo' => 'bar']);

        self::assertEquals('zoo', $this->queue->name());
        self::assertEquals(41, $this->queue->consumerCount());
        self::assertEquals(59, $this->queue->messagesCount());
    }

    /**
     * Queue should send queue.delete frame.
     */
    public function testDelete()
    {
        $this->channel->expects(self::once())
            ->method('send')
            ->with(new QueueDelete(0, 'foo', true, false, true));

        $this->channel->expects(self::never())
            ->method('wait');

        $this->queue->delete(Queue::FLAG_IF_UNUSED | Queue::FLAG_NO_WAIT);
    }

    /**
     * Queue should send queue.delete and wait for reply.
     */
    public function testDeleteBlocking()
    {
        $this->channel->expects(self::once())
            ->method('send')
            ->with(new QueueDelete(0, 'foo', true, false, false));

        $this->channel->expects(self::once())
            ->method('wait')
            ->with(QueueDeleteOk::class)
            ->willReturn(new QueueDeleteOk(59));

        $this->queue->delete(Queue::FLAG_IF_UNUSED);

        self::assertEquals(59, $this->queue->messagesCount());
    }

    /**
     * Queue should send queue.purge frame.
     */
    public function testPurge()
    {
        $this->channel->expects(self::once())
            ->method('send')
            ->with(new QueuePurge(0, 'foo', true));

        $this->channel->expects(self::never())
            ->method('wait');

        $this->queue->purge(Queue::FLAG_NO_WAIT);
    }

    /**
     * Queue should send queue.purge and wait for reply.
     */
    public function testPurgeBlocking()
    {
        $this->channel->expects(self::once())
            ->method('send')
            ->with(new QueuePurge(0, 'foo', false));

        $this->channel->expects(self::once())
            ->method('wait')
            ->with(QueuePurgeOk::class)
            ->willReturn(new QueuePurgeOk(41));

        $this->queue->purge();

        self::assertEquals(41, $this->queue->messagesCount());
    }

    /**
     * Queue should send queue.bind frame.
     */
    public function testBind()
    {
        $this->channel->expects(self::once())
            ->method('send')
            ->with(new QueueBind(0, 'foo', 'bar', 'baz', true, ['foo' => 'bar']));

        $this->channel->expects(self::never())
            ->method('wait');

        $this->queue->bind('bar', 'baz', ['foo' => 'bar'], Queue::FLAG_NO_WAIT);
    }

    /**
     * Queue should send queue.bind and wait for reply.
     */
    public function testBindBlocking()
    {
        $this->channel->expects(self::once())
            ->method('send')
            ->with(new QueueBind(0, 'foo', 'bar', 'baz', false, ['foo' => 'bar']));

        $this->channel->expects(self::once())
            ->method('wait')
            ->with(QueueBindOk::class);

        $this->queue->bind('bar', 'baz', ['foo' => 'bar']);
    }

    /**
     * Queue should send queue.unbind and wait for reply.
     */
    public function testUnbindBlocking()
    {
        $this->channel->expects(self::once())
            ->method('send')
            ->with(new QueueUnbind(0, 'foo', 'bar', 'baz', ['foo' => 'bar']));

        $this->channel->expects(self::once())
            ->method('wait')
            ->with(QueueUnbindOk::class);

        $this->queue->unbind('bar', 'baz', ['foo' => 'bar']);
    }

    /**
     * Queue should know its name.
     */
    public function testName()
    {
        self::assertEquals('foo', $this->queue->name());
    }

    /**
     * Queue should cast to string as tag name.
     */
    public function testToString()
    {
        self::assertEquals('foo', $this->queue);
    }
}
