<?php

namespace ButterAMQPTest\Integration\RabbitMQ;

use ButterAMQP\Message;
use ButterAMQP\AMQP091\Queue;

/**
 * @group slow
 * @group integration
 */
class TxPublishModeTest extends TestCase
{
    public function testPublishTxCommit()
    {
        $channel = $this->connection->open()
            ->channel();

        $channel->selectTx();

        $queue = $channel->queue(uniqid('test-'))
            ->define(Queue::FLAG_AUTO_DELETE);

        // Publish some messages
        $channel->publish(new Message(''), '', $queue);
        $channel->publish(new Message(''), '', $queue);
        $channel->publish(new Message(''), '', $queue);

        // Update message count
        $queue->define(Queue::FLAG_AUTO_DELETE);

        // There are no messages in the queue because transaction is not committed
        self::assertEquals(0, $queue->messagesCount());

        // Commit transaction
        $channel->txCommit();

        // Update message count
        $queue->define(Queue::FLAG_AUTO_DELETE);

        // Messages are enqueued
        self::assertEquals(3, $queue->messagesCount());
    }

    public function testPublishTxRollback()
    {
        $channel = $this->connection->open()
            ->channel();

        $channel->selectTx();

        $queue = $channel->queue(uniqid('test-'))
            ->define(Queue::FLAG_AUTO_DELETE);

        // Publish 2 messages
        $channel->publish(new Message(''), '', $queue);
        $channel->publish(new Message(''), '', $queue);

        // Rollback everything published so far
        $channel->txRollback();

        // Publish new message, into new transaction
        $channel->publish(new Message(''), '', $queue);

        // Update message count
        $queue->define(Queue::FLAG_AUTO_DELETE);

        // There are no messages in the queue because transaction is not committed
        self::assertEquals(0, $queue->messagesCount());

        // Commit transaction, with one message
        $channel->txCommit();

        // Update message count
        $queue->define(Queue::FLAG_AUTO_DELETE);

        // Messages are enqueued
        self::assertEquals(1, $queue->messagesCount());
    }
}
