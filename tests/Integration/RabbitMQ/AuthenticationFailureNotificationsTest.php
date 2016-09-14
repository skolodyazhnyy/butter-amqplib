<?php

namespace ButterAMQPTest\Integration\RabbitMQ;

use ButterAMQP\AMQP091\Connection;
use ButterAMQP\Exception\AMQP\AccessRefusedException;
use ButterAMQP\Url;

/**
 * @group slow
 * @group integration
 */
class AuthenticationFailureNotificationsTest extends TestCase
{
    public function testFailure()
    {
        $this->expectException(AccessRefusedException::class);

        $url = Url::parse($_SERVER['RABBITMQ_URL']);

        $url = new Url(
            $url->getScheme(),
            $url->getHost(),
            $url->getPort(),
            $url->getUser(),
            $url->getPassword().'INVALID',
            $url->getVhost(),
            $url->getQuery()
        );

        $connection = new Connection($url, $this->wire);

        $connection->open()
            ->channel();
    }
}
