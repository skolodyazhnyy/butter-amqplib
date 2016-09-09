<?php

namespace ButterAMQPTest\Integration\RabbitMQ;

use ButterAMQP\Message;
use ButterAMQP\AMQP091\Queue;

/**
 * @group slow
 * @group integration
 */
class BasicRecoverTest extends TestCase
{
    public function testGetMessage()
    {
        $tokenFoo = uniqid('token-foo-');
        $tokenBar = uniqid('token-bar-');

        $channel = $this->connection->open()
            ->channel();

        $queue = $channel->queue()
            ->define(Queue::FLAG_AUTO_DELETE | Queue::FLAG_EXCLUSIVE);

        $channel->publish(new Message($tokenFoo), '', $queue);
        $channel->publish(new Message($tokenBar), '', $queue);

        // Fetch few messages and requeue
        $deliveryFooOne = $channel->get($queue, true);
        $deliveryBarOne = $channel->get($queue, true);

        // Both deliveries are not acknowledged, so we recover and requeue them
        $channel->recover(true);

        // Fetch messages again
        $deliveryFooTwo = $channel->get($queue, true);
        $deliveryBarTwo = $channel->get($queue, true);

        $this->connection->close();

        self::assertNotNull($deliveryFooOne, "Message 'foo' was not published on first place");
        self::assertNotNull($deliveryFooTwo, "Message 'bar' was not published on first place");
        self::assertNotNull($deliveryBarOne, "Message 'foo' was not re-delivered");
        self::assertNotNull($deliveryBarTwo, "Message 'bar' was not re-delivered");

        self::assertEquals($tokenFoo, $deliveryFooOne->getBody(), "Original 'foo' message body does not match");
        self::assertEquals($tokenFoo, $deliveryFooTwo->getBody(), "Re-delivered 'foo' message body does not match");
        self::assertEquals($tokenBar, $deliveryBarOne->getBody(), "Original 'bar' message body does not match");
        self::assertEquals($tokenBar, $deliveryBarTwo->getBody(), "Re-delivered 'bar' message body does not match");

        self::assertFalse($deliveryFooOne->isRedeliver(), "Original 'foo' message should not be marked as re-delivered");
        self::assertTrue($deliveryFooTwo->isRedeliver(), "Re-delivered 'foo' message should be marked as re-delivered");
        self::assertFalse($deliveryBarOne->isRedeliver(), "Original 'bar' message should not be marked as re-delivered");
        self::assertTrue($deliveryBarTwo->isRedeliver(), "Re-delivered 'bar' message should be marked as re-delivered");
    }
}
