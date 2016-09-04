<?php

namespace ButterAMQP\IO;

use ButterAMQP\Binary;
use ButterAMQP\IOInterface;
use ButterAMQP\Binary\ReadableBinaryData;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

class StreamIO implements IOInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var resource|null
     */
    private $stream;

    /**
     * @var string
     */
    private $buffer;

    /**
     * Initialize default logger.
     */
    public function __construct()
    {
        $this->logger = new NullLogger();
    }

    /**
     * {@inheritdoc}
     */
    public function open($host, $port)
    {
        if ($this->stream) {
            return $this;
        }

        $this->logger->debug(sprintf('Connecting to "%s"...', $host.':'.$port), [
            'host' => $host,
            'port' => $port,
        ]);

        $this->stream = fsockopen($host, intval($port), $errno, $errstr, 30);

        if (!$this->stream) {
            throw new \RuntimeException(sprintf(
                'Unable to connect to %s using stream socket: %s',
                $host.':'.$port,
                $errstr
            ));
        }

        $this->logger->debug(sprintf('Connection established'), [
            'host' => $host,
            'port' => $port,
        ]);

        $this->buffer = '';

        return $this;
    }

    /**
     * @return $this
     */
    public function close()
    {
        if ($this->stream) {
            fclose($this->stream);
            $this->stream = null;

            $this->logger->debug('Connection closed');
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function peek($length, $blocking = true, $timeout = null)
    {
        $received = Binary::length($this->buffer);

        if ($received >= $length) {
            return $this->buffer;
        }

        $this->buffer .= $this->recv($length - $received, $blocking, $timeout);

        if (Binary::length($this->buffer) >= $length) {
            return $this->buffer;
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function read($length, $blocking = true, $timeout = null)
    {
        if (!$this->peek($length, $blocking, $timeout)) {
            return null;
        }

        $data = Binary::subset($this->buffer, 0, $length);

        $this->buffer = Binary::subset($this->buffer, $length);

        return $data;
    }

    /**
     * @param int  $length
     * @param bool $blocking
     * @param int  $timeout
     *
     * @return string
     */
    private function recv($length, $blocking, $timeout = null)
    {
        list($sec, $usec) = explode('|', number_format($timeout, 6, '|', ''));

        stream_set_timeout($this->stream, $sec, $usec);
        stream_set_blocking($this->stream, $blocking);

        $buffer = '';

        $received = fread($this->stream, $length);
        if ($received === false) {
            throw new \RuntimeException('An error occur while reading from the socket');
        }

        $buffer .= $received;

        if ($buffer) {
            $this->logger->debug(new ReadableBinaryData('Receive', $buffer));
        }

        return $buffer;
    }

    /**
     * @param string   $data
     * @param int|null $length
     *
     * @return $this
     */
    public function write($data, $length = null)
    {
        if ($length === null) {
            $length = Binary::length($data);
        }

        $this->logger->debug(new ReadableBinaryData('Sending', $data));

        while ($length > 0) {
            $written = fwrite($this->stream, $data, $length);

            if ($written === false) {
                throw new \RuntimeException('An error occur while writing to socket');
            }

            $length -= $written;
            $data = $length ? Binary::subset($data, $written, $length) : '';
        }

        return $this;
    }
}
