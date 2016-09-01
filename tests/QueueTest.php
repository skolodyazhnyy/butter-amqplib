<?php

namespace ButterAMQPTest;

use ButterAMQP\Framing\Method\QueuePurge;
use ButterAMQP\Framing\Method\QueuePurgeOk;
use ButterAMQP\Queue;
use ButterAMQP\Framing\Method\QueueBind;
use ButterAMQP\Framing\Method\QueueBindOk;
use ButterAMQP\Framing\Method\QueueDeclare;
use ButterAMQP\Framing\Method\QueueDeclareOk;
use ButterAMQP\Framing\Method\QueueDelete;
use ButterAMQP\Framing\Method\QueueDeleteOk;
use ButterAMQP\Framing\Method\QueueUnbind;
use ButterAMQP\Framing\Method\QueueUnbindOk;
use ButterAMQP\WireInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;

class QueueTest extends TestCase
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
     * @var Queue
     */
    private $queue;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->wire = $this->createMock(WireInterface::class);
        $this->channel = 51;
        $this->queue = new Queue($this->wire, $this->channel, 'foo');
    }

    /**
     * Queue should send queue.declare frame.
     */
    public function testDeclare()
    {
        $this->wire->expects(self::once())
            ->method('send')
            ->with($this->channel, new QueueDeclare(0, 'foo', true, true, false, false, true, ['foo' => 'bar']));

        $this->wire->expects(self::never())
            ->method('wait');

        $flags = Queue::FLAG_DURABLE | Queue::FLAG_PASSIVE | Queue::FLAG_NO_WAIT;

        $this->queue->define($flags, ['foo' => 'bar']);
    }

    /**
     * Queue should send queue.declare and wait for reply.
     */
    public function testDeclareBlocking()
    {
        $this->wire->expects(self::once())
            ->method('send')
            ->with($this->channel, new QueueDeclare(0, 'foo', true, true, false, false, false, ['foo' => 'bar']));

        $this->wire->expects(self::once())
            ->method('wait')
            ->with($this->channel, QueueDeclareOk::class)
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
        $this->wire->expects(self::once())
            ->method('send')
            ->with($this->channel, new QueueDelete(0, 'foo', true, false, true));

        $this->wire->expects(self::never())
            ->method('wait');

        $this->queue->delete(Queue::FLAG_IF_UNUSED | Queue::FLAG_NO_WAIT);
    }

    /**
     * Queue should send queue.delete and wait for reply.
     */
    public function testDeleteBlocking()
    {
        $this->wire->expects(self::once())
            ->method('send')
            ->with($this->channel, new QueueDelete(0, 'foo', true, false, false));

        $this->wire->expects(self::once())
            ->method('wait')
            ->with($this->channel, QueueDeleteOk::class)
            ->willReturn(new QueueDeleteOk(59));

        $this->queue->delete(Queue::FLAG_IF_UNUSED);

        self::assertEquals(59, $this->queue->messagesCount());
    }

    /**
     * Queue should send queue.purge frame.
     */
    public function testPurge()
    {
        $this->wire->expects(self::once())
            ->method('send')
            ->with($this->channel, new QueuePurge(0, 'foo', true));

        $this->wire->expects(self::never())
            ->method('wait');

        $this->queue->purge(Queue::FLAG_NO_WAIT);
    }

    /**
     * Queue should send queue.purge and wait for reply.
     */
    public function testPurgeBlocking()
    {
        $this->wire->expects(self::once())
            ->method('send')
            ->with($this->channel, new QueuePurge(0, 'foo', false));

        $this->wire->expects(self::once())
            ->method('wait')
            ->with($this->channel, QueuePurgeOk::class)
            ->willReturn(new QueuePurgeOk(41));

        $this->queue->purge();

        self::assertEquals(41, $this->queue->messagesCount());
    }

    /**
     * Queue should send queue.bind frame.
     */
    public function testBind()
    {
        $this->wire->expects(self::once())
            ->method('send')
            ->with($this->channel, new QueueBind(0, 'foo', 'bar', 'baz', true, ['foo' => 'bar']));

        $this->wire->expects(self::never())
            ->method('wait');

        $this->queue->bind('bar', 'baz', ['foo' => 'bar'], Queue::FLAG_NO_WAIT);
    }

    /**
     * Queue should send queue.bind and wait for reply.
     */
    public function testBindBlocking()
    {
        $this->wire->expects(self::once())
            ->method('send')
            ->with($this->channel, new QueueBind(0, 'foo', 'bar', 'baz', false, ['foo' => 'bar']));

        $this->wire->expects(self::once())
            ->method('wait')
            ->with($this->channel, QueueBindOk::class);

        $this->queue->bind('bar', 'baz', ['foo' => 'bar']);
    }

    /**
     * Queue should send queue.unbind and wait for reply.
     */
    public function testUnbindBlocking()
    {
        $this->wire->expects(self::once())
            ->method('send')
            ->with($this->channel, new QueueUnbind(0, 'foo', 'bar', 'baz', ['foo' => 'bar']));

        $this->wire->expects(self::once())
            ->method('wait')
            ->with($this->channel, QueueUnbindOk::class);

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
