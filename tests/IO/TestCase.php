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
     * @var string
     */
    protected $serverProtocol;

    /**
     * @var string
     */
    protected $serverCert;

    /**
     * @var resource
     */
    protected $control;

    /**
     * Starts the server and establish control connection.
     *
     * @param bool $secure
     */
    protected function serverStart($secure = false)
    {
        $this->serverProtocol = $secure ? 'ssl' : 'tcp';
        $this->serverHost = '127.0.0.1';
        $this->serverPort = rand(23000, 24000);
        $this->serverCert = __DIR__.DIRECTORY_SEPARATOR.'server/cert.pem';

        $this->serverProcess = new Process(implode(' ', [
            escapeshellcmd(PHP_BINARY),
            escapeshellarg(__DIR__.DIRECTORY_SEPARATOR.'server/run.php'),
            escapeshellarg($this->serverProtocol),
            escapeshellarg($this->serverHost),
            escapeshellarg($this->serverPort),
        ]));

        $this->serverProcess->start(function ($type, $output) {
            // echo $output;
        });

        $this->setUpControlConnection();
    }

    /**
     * Establish control connection.
     */
    protected function setUpControlConnection()
    {
        $context = stream_context_create();

        if ($this->serverProtocol === 'ssl') {
            stream_context_set_option($context, 'ssl', 'local_cert', $this->serverCert);
            stream_context_set_option($context, 'ssl', 'allow_self_signed', true);
            stream_context_set_option($context, 'ssl', 'verify_peer', false);
        }

        for ($i = 0; $i < 5; ++$i) {
            $this->control = @stream_socket_client(
                sprintf('%s://%s:%d', $this->serverProtocol, $this->serverHost, $this->serverPort),
                $errno,
                $errstr,
                1,
                STREAM_CLIENT_CONNECT,
                $context
            );

            if (is_resource($this->control)) {
                break;
            }

            usleep(100000);
        }

        if (!is_resource($this->control)) {
            self::markTestSkipped('An error occur when establishing control connection to the server: '.$errstr);
        }

        stream_set_timeout($this->control, 1);
    }

    /**
     * Sends a signal to the server to close connection when all pending data is read.
     */
    protected function serverStop()
    {
        $this->serverWriteStopSymbol();
    }

    /**
     * Stops the server if running.
     */
    protected function serverForceStop()
    {
        $this->serverWriteStopSymbol();

        if ($this->serverProcess->isRunning()) {
            $this->serverProcess->stop(2);
        }

        return $this->serverProcess->getExitCode();
    }

    /**
     * @param string $data
     * @param int    $length
     *
     * @return int
     */
    protected function serverWrite($data, $length = null)
    {
        if (strpos($data, "\xCE") !== false) {
            throw new \LogicException('Symbol "\xCE" is used to close connection, you should not use it in test');
        }

        $write = fwrite(
            $this->control,
            $data,
            $length === null ? Binary::length($data) : $length
        );

        fflush($this->control);

        return $write;
    }

    /**
     * Send server a command to stop.
     */
    protected function serverWriteStopSymbol()
    {
        if (!$this->serverProcess->isRunning()) {
            return null;
        }

        for ($retry = 0; $retry < 10 && !feof($this->control); ++$retry) {
            if (!@fwrite($this->control, str_repeat("\xCE", 100), 100)) {
                break;
            }

            @fflush($this->control);
            usleep(100000);
        }
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
     * @param string $message
     */
    protected function assertServerReceive($data, $message = '')
    {
        self::assertEquals($data, $this->serverRead(Binary::length($data)), $message);
    }

    /**
     * Kill echo server.
     */
    protected function tearDown()
    {
        $exitCode = $this->serverForceStop();

        if ($exitCode && $exitCode != 143) {
            self::markTestSkipped(sprintf(
                "Echo server exited with code %d:\n%s",
                $this->serverProcess->getExitCode(),
                $this->serverProcess->getErrorOutput()
            ));
        }
    }
}
