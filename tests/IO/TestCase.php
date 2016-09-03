<?php

namespace ButterAMQPTest\IO;

use ButterAMQP\Binary;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Symfony\Component\Process\Process;

class TestCase extends BaseTestCase
{
    /**
     * @var Process
     */
    protected $echoServer;

    /**
     * @var string
     */
    protected $host;

    /**
     * @var int
     */
    protected $port;

    /**
     * @var resource
     */
    protected $control;

    /**
     * Start echo server.
     */
    protected function setUp()
    {
        $this->port = rand(23000, 24000);
        $this->host = '127.0.0.1';

        $this->echoServer = new Process(implode(' ', [
            escapeshellcmd('php'),
            escapeshellarg(__DIR__.DIRECTORY_SEPARATOR.'echo-server.php'),
            escapeshellarg($this->host.':'.$this->port),
        ]));

        $this->echoServer->start();

        for ($i = 0; $i < 5; ++$i) {
            $this->control = @fsockopen($this->host, $this->port, $errno, $errstr, 5);

            if (is_resource($this->control)) {
                break;
            }

            usleep(100000);
        }

        if (!is_resource($this->control)) {
            self::markTestSkipped('An error occur when establishing control connection to echo server');
        }

        stream_set_timeout($this->control, 1);
    }

    /**
     * Kill echo server.
     */
    protected function tearDown()
    {
        if ($this->echoServer->isRunning()) {
            $this->echoServer->stop(2, 2);
        }

        $exitCode = $this->echoServer->getExitCode();

        if ($exitCode && $exitCode != 143) {
            self::markTestSkipped(sprintf(
                "Echo server exited with code %d:\n%s",
                $this->echoServer->getExitCode(),
                $this->echoServer->getErrorOutput()
            ));
        }
    }

    /**
     * @param string $data
     * @param string $message
     */
    protected function assertServerReceive($data, $message = '')
    {
        self::assertEquals($data, $this->serverRead(Binary::length($data)), $message);
    }

    /**
     * @param int $length
     *
     * @return string
     */
    protected function serverRead($length)
    {
        return fread($this->control, $length);
    }

    /**
     * @param string $data
     * @param int    $length
     *
     * @return int
     */
    protected function serverWrite($data, $length = null)
    {
        $write = fwrite(
            $this->control,
            $data,
            $length === null ? Binary::length($data) : $length
        );

        fflush($this->control);

        return $write;
    }
}
