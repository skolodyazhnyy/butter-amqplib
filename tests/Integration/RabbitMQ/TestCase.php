<?php

namespace ButterAMQPTest\Integration\RabbitMQ;

use ButterAMQP\Connection;
use ButterAMQP\ConnectionInterface;
use ButterAMQP\IO\StreamIO;
use ButterAMQP\IOInterface;
use ButterAMQP\Wire;
use ButterAMQP\WireInterface;
use PHPUnit_Framework_TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    /**
     * @var ConnectionInterface
     */
    protected $connection;

    /**
     * @var IOInterface
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

        $this->io = new StreamIO();
        $this->wire = new Wire($this->io);
        $this->connection = new Connection($_SERVER['RABBITMQ_URL'], $this->wire);
    }
}
