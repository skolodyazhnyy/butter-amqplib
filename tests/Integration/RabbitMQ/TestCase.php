<?php

namespace AMQLibTest\Integration\RabbitMQ;

use AMQLib\Connection;
use AMQLib\ConnectionInterface;
use AMQLib\InputOutput\SocketInputOutput;
use AMQLib\InputOutputInterface;
use AMQLib\Wire;
use AMQLib\WireInterface;
use PHPUnit_Framework_TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    /**
     * @var ConnectionInterface
     */
    protected $connection;

    /**
     * @var InputOutputInterface
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

        $this->io = new SocketInputOutput();
        $this->wire = new Wire($this->io);
        $this->connection = new Connection($_SERVER['RABBITMQ_URL'], $this->wire);
    }
}
