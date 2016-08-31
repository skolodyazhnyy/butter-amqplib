<?php

namespace AMQLibTest\Integration\RabbitMQ;

use AMQLib\Connection;
use AMQLib\ConnectionInterface;
use AMQLib\InputOutput\SocketInputOutput;
use AMQLibTest\Integration\RecorderDecoratedInputOutput;
use PHPUnit_Framework_TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    /**
     * @var ConnectionInterface
     */
    protected $connection;

    /**
     * @var RecorderDecoratedInputOutput
     */
    protected $io;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        if (!isset($_SERVER['RABBITMQ_URL'])) {
            self::markTestSkipped('Environment variable RABBITMQ_URL is not set.');
        }

        $this->io = new RecorderDecoratedInputOutput(new SocketInputOutput());
        $this->connection = new Connection($_SERVER['RABBITMQ_URL'], $this->io);
    }

    /**
     * @param string $data
     * @param string $message
     */
    public function assertDataSent($data, $message = '')
    {
        self::assertContains($data, $this->io->getSent(), $message);
    }

    /**
     * @param string $data
     * @param string $message
     */
    public function assertDataReceived($data, $message = '')
    {
        self::assertContains($data, $this->io->getReceived(), $message);
    }

    /**
     * This assert seek method signature in the data received from the server, it may give false positives.
     *
     * @param int    $class
     * @param int    $method
     * @param int    $channel
     * @param string $message
     */
    public function assertMethodReceived($class, $method, $channel, $message = '')
    {
        $this->assertStreamHasMethodSignature($class, $method, $channel, $this->io->getReceived(), $message);
    }

    /**
     * This assert seek method signature in the data sent to the server, it may give false positives.
     *
     * @param int    $class
     * @param int    $method
     * @param int    $channel
     * @param string $message
     */
    public function assertMethodSent($class, $method, $channel, $message = '')
    {
        $this->assertStreamHasMethodSignature($class, $method, $channel, $this->io->getSent(), $message);
    }

    /**
     * This assert seek method signature in the given byte-string, it may give false positives.
     *
     * @todo probably it worth breaking stream into frames and check them individually
     *
     * @param int    $class
     * @param int    $method
     * @param int    $channel
     * @param string $data
     * @param string $message
     */
    private function assertStreamHasMethodSignature($class, $method, $channel, $data, $message = '')
    {
        $position = 0;

        while (($position = strpos($data, "\x01".pack('n', $channel), $position)) !== false) {
            $position += 7; // skip message header
            $signature = unpack('nclass/nmethod', substr($data, $position, 4));

            if ($signature['class'] == $class && $signature['method'] == $method) {
                return; // OK
            }
        }

        self::fail($message ?: sprintf('Data stream expected to have signature for method %d, %d at channel %d', $class, $method, $channel));
    }
}
