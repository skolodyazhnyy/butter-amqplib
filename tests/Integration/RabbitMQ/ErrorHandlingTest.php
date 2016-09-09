<?php

namespace ButterAMQPTest\Integration\RabbitMQ;

use ButterAMQP\Exception\AMQP\NotFoundException;
use ButterAMQP\Exception\AMQP\PreconditionFailedException;
use ButterAMQP\AMQP091\Exchange;
use ButterAMQP\Message;

/**
 * @group slow
 * @group integration
 */
class ErrorHandlingTest extends TestCase
{
    public function testConnectionErrorHandling()
    {
        $this->expectException(PreconditionFailedException::class);

        $channel = $this->connection->open()
            ->channel();

        $channel->exchange(uniqid('errors-'))
            ->define('topic')
            ->define('direct');
    }

    public function testChannelErrorHandling()
    {
        $this->expectException(NotFoundException::class);

        $this->connection->open()
            ->channel()
            ->publish(new Message(''), uniqid('errors-'));

        $this->connection->serve(true, 1);
    }
}
