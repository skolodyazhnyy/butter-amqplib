<?php

namespace ButterAMQPTest\Integration\RabbitMQ;

use ButterAMQP\Queue;

/**
 * @group slow
 * @group integration
 */
class ConsumerCancelNotificationTest extends TestCase
{
    public function testNotification()
    {
        $channel = $this->connection->open()
            ->channel();

        $queue = $channel->queue()
            ->define(Queue::FLAG_AUTO_DELETE);

        $consumer = $channel->consume($queue, function () {
        });

        self::assertTrue($consumer->isActive());
        self::assertTrue($channel->hasConsumer($consumer));

        $queue->delete();

        $this->connection->serve();

        self::assertFalse($consumer->isActive());
        self::assertFalse($channel->hasConsumer($consumer));
    }
}
