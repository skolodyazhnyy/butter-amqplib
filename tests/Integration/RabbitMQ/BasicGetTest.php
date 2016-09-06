<?php

namespace ButterAMQPTest\Integration\RabbitMQ;

use ButterAMQP\Message;
use ButterAMQP\Queue;

/**
 * @group slow
 * @group integration
 */
class BasicGetTest extends TestCase
{
    public function testGetMessage()
    {
        $token = uniqid('token-');

        $channel = $this->connection->open()
            ->channel();

        $queue = $channel->queue()
            ->define(Queue::FLAG_AUTO_DELETE | Queue::FLAG_EXCLUSIVE);

        $channel->publish(new Message($token), '', $queue);

        $delivery = $channel->get($queue, true);
        $delivery->ack();

        $this->connection->close();

        self::assertEquals($token, $delivery->getBody());
        self::assertEmpty($delivery->getConsumerTag());
        self::assertNotEmpty($delivery->getDeliveryTag());
        self::assertEquals('', $delivery->getExchange());
        self::assertEquals($queue->name(), $delivery->getRoutingKey());
    }

    public function testGetEmpty()
    {
        $channel = $this->connection->open()
            ->channel();

        $queue = $channel->queue()
            ->define(Queue::FLAG_AUTO_DELETE | Queue::FLAG_EXCLUSIVE);

        $delivery = $channel->get($queue, true);

        $this->connection->close();

        self::assertNull($delivery);
    }
}
