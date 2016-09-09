<?php

namespace ButterAMQPTest\Integration\RabbitMQ;

use ButterAMQP\Message;
use ButterAMQP\AMQP091\Queue;

/**
 * @group slow
 * @group integration
 */
class TxAckModeTest extends TestCase
{
    public function testPublishTxCommit()
    {
        $channel = $this->connection->open()
            ->channel();

        // Select Transaction mode
        $channel->selectTx();

        $queue = $channel->queue(uniqid('test-'))
            ->define(Queue::FLAG_AUTO_DELETE);

        // Publish some messages
        $channel->publish(new Message(''), '', $queue);
        $channel->publish(new Message(''), '', $queue);
        $channel->publish(new Message(''), '', $queue);

        // Commit 3 published messages
        $channel->txCommit();

        // Update message count
        $queue->define(Queue::FLAG_AUTO_DELETE);

        // There are 3 messages in the queue
        self::assertEquals(3, $queue->messagesCount());

        // Ack few messages
        $channel->get($queue)
            ->reject(true);

        $channel->get($queue)
            ->reject(true);

        // Update message count
        $queue->define(Queue::FLAG_AUTO_DELETE);

        // There are only 1 message in the queue, because reject was not committed.
        // Two other messages are still in the queue, but already assigned to consumer.
        self::assertEquals(1, $queue->messagesCount());

        // Commit pending acknowledgments: reject and re-enqueue two messages delivered before.
        $channel->txCommit();

        // Update message count
        $queue->define(Queue::FLAG_AUTO_DELETE);

        // There are all 3 messages back in the queue.
        self::assertEquals(3, $queue->messagesCount());
    }

    public function testPublishTxRollback()
    {
        $channel = $this->connection->open()
            ->channel();

        // Select Transaction mode
        $channel->selectTx();

        $queue = $channel->queue(uniqid('test-'))
            ->define(Queue::FLAG_AUTO_DELETE);

        // Publish some messages
        $channel->publish(new Message(''), '', $queue);
        $channel->publish(new Message(''), '', $queue);
        $channel->publish(new Message(''), '', $queue);

        // Commit 3 published messages
        $channel->txCommit();

        // Update message count
        $queue->define(Queue::FLAG_AUTO_DELETE);

        // There are 3 messages in the queue
        self::assertEquals(3, $queue->messagesCount());

        // Ack few messages
        $channel->get($queue)
            ->reject(true);

        $channel->get($queue)
            ->reject(true);

        // Update message count
        $queue->define(Queue::FLAG_AUTO_DELETE);

        // There are only 1 message in the queue, because reject was not committed.
        // Two other messages are still in the queue, but already assigned to consumer.
        self::assertEquals(1, $queue->messagesCount());

        // Rollback all acknowledgments: pretend these two rejects are never happened.
        $channel->txRollback();

        // Update message count
        $queue->define(Queue::FLAG_AUTO_DELETE);

        // There are only 1 message in the queue, because two others are still not acknowledged.
        self::assertEquals(1, $queue->messagesCount());
    }
}
