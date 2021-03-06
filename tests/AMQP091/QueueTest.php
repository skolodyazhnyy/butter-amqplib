<?php

namespace ButterAMQPTest\AMQP091;

use ButterAMQP\AMQP091\Framing\Method\QueuePurge;
use ButterAMQP\AMQP091\Framing\Method\QueuePurgeOk;
use ButterAMQP\AMQP091\Queue;
use ButterAMQP\AMQP091\Framing\Method\QueueBind;
use ButterAMQP\AMQP091\Framing\Method\QueueBindOk;
use ButterAMQP\AMQP091\Framing\Method\QueueDeclare;
use ButterAMQP\AMQP091\Framing\Method\QueueDeclareOk;
use ButterAMQP\AMQP091\Framing\Method\QueueDelete;
use ButterAMQP\AMQP091\Framing\Method\QueueDeleteOk;
use ButterAMQP\AMQP091\Framing\Method\QueueUnbind;
use ButterAMQP\AMQP091\Framing\Method\QueueUnbindOk;
use ButterAMQP\AMQP091\WireInterface;
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
            ->with(new QueueDeclare($this->channel, 0, 'foo', true, true, false, false, true, ['foo' => 'bar']));

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
            ->with(new QueueDeclare($this->channel, 0, 'foo', true, true, false, false, false, ['foo' => 'bar']));

        $this->wire->expects(self::once())
            ->method('wait')
            ->with($this->channel, QueueDeclareOk::class)
            ->willReturn(new QueueDeclareOk($this->channel, 'zoo', 59, 41));

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
            ->with(new QueueDelete($this->channel, 0, 'foo', true, false, true));

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
            ->with(new QueueDelete($this->channel, 0, 'foo', true, false, false));

        $this->wire->expects(self::once())
            ->method('wait')
            ->with($this->channel, QueueDeleteOk::class)
            ->willReturn(new QueueDeleteOk($this->channel, 59));

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
            ->with(new QueuePurge($this->channel, 0, 'foo', true));

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
            ->with(new QueuePurge($this->channel, 0, 'foo', false));

        $this->wire->expects(self::once())
            ->method('wait')
            ->with($this->channel, QueuePurgeOk::class)
            ->willReturn(new QueuePurgeOk($this->channel, 41));

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
            ->with(new QueueBind($this->channel, 0, 'foo', 'bar', 'baz', true, ['foo' => 'bar']));

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
            ->with(new QueueBind($this->channel, 0, 'foo', 'bar', 'baz', false, ['foo' => 'bar']));

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
            ->with(new QueueUnbind($this->channel, 0, 'foo', 'bar', 'baz', ['foo' => 'bar']));

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
