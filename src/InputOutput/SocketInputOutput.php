<?php

namespace ButterAMQP\InputOutput;

use ButterAMQP\Binary;
use ButterAMQP\InputOutputInterface;
use ButterAMQP\Binary\ReadableBinaryData;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

class SocketInputOutput implements InputOutputInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var int|null
     */
    private $timeout;

    /**
     * @var resource|null
     */
    private $socket;

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
        if ($this->socket) {
            return $this;
        }

        $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

        if ($this->socket === false) {
            throw new \RuntimeException('Unable to create socket: '.socket_strerror(socket_last_error()));
        }

        $this->logger->debug(sprintf('Connecting to "%s"...', $host.':'.$port), [
            'host' => $host,
            'port' => $port,
        ]);

        if (socket_connect($this->socket, $host, $port) === false) {
            throw new \RuntimeException(sprintf(
                'Unable to connect to %s: %s',
                $host.':'.$port,
                socket_strerror(socket_last_error())
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
        if ($this->socket) {
            socket_close($this->socket);

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

        socket_set_option($this->socket, SOL_SOCKET, SO_RCVTIMEO, [
            'sec' => $sec,
            'usec' => $usec,
        ]);

        $buffer = '';

        $received = socket_recv(
            $this->socket,
            $buffer,
            $length,
            ($blocking ? 0 : MSG_DONTWAIT)
        );

        if ($received === false) {
            $errno = socket_last_error($this->socket);

            if ($errno == SOCKET_EAGAIN || $errno == SOCKET_EWOULDBLOCK) {
                return '';
            }

            throw new \RuntimeException(
                'An error occur while reading from the socket: '.
                socket_strerror($errno)
            );
        }

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
            $written = socket_write($this->socket, $data, $length);

            if ($written === false) {
                throw new \RuntimeException(
                    'An error occur while writing to socket: '.
                    socket_strerror(socket_last_error($this->socket))
                );
            }

            $length -= $written;
            $data = $length ? Binary::subset($data, $written, $length) : '';
        }

        return $this;
    }
}
