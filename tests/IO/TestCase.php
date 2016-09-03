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
    protected $serverProcess;

    /**
     * @var string
     */
    protected $serverHost;

    /**
     * @var int
     */
    protected $serverPort;

    /**
     * @var resource
     */
    protected $control;

    /**
     * Start echo server.
     */
    protected function setUp()
    {
        $this->serverHost = '127.0.0.1';
        $this->serverPort = rand(23000, 24000);

        $this->serverProcess = new Process(implode(' ', [
            escapeshellcmd('php'),
            escapeshellarg(__DIR__.DIRECTORY_SEPARATOR.'echo-server.php'),
            escapeshellarg($this->serverHost.':'.$this->serverPort),
        ]));

        $this->serverProcess->start();

        $this->setUpControlConnection();
    }

    /**
     * Establish control connection.
     */
    protected function setUpControlConnection()
    {
        for ($i = 0; $i < 5; ++$i) {
            $this->control = @fsockopen($this->serverHost, $this->serverPort, $errno, $errstr, 5);

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

    /**
     * Kill echo server.
     */
    protected function tearDown()
    {
        if ($this->serverProcess->isRunning()) {
            $this->serverProcess->stop(2, 2);
        }

        $exitCode = $this->serverProcess->getExitCode();

        if ($exitCode && $exitCode != 143) {
            self::markTestSkipped(sprintf(
                "Echo server exited with code %d:\n%s",
                $this->serverProcess->getExitCode(),
                $this->serverProcess->getErrorOutput()
            ));
        }
    }
}
