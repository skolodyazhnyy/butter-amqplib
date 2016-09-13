<?php

namespace ButterAMQPTest\Integration\RabbitMQ;

use ButterAMQP\AMQP091\Connection;
use ButterAMQP\IO\StreamIO;
use ButterAMQP\Url;
use ButterAMQP\AMQP091\Wire;
use ButterAMQP\AMQP091\WireInterface;
use PHPUnit_Framework_TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var StreamIO
     */
    protected $io;

    /**
     * @var WireInterface
     */
    protected $wire;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        if (!isset($_SERVER['RABBITMQ_URL'])) {
            self::markTestSkipped('Environment variable RABBITMQ_URL is not set.');
        }

        $this->io = new StreamIO(1, 1);
        $this->wire = new Wire($this->io);
        $this->connection = new Connection(Url::parse($_SERVER['RABBITMQ_URL']), $this->wire);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        if ($this->io->isOpen()) {
            $this->connection->close();
        }
    }
}
