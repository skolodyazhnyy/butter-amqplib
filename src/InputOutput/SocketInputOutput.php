<?php

namespace AMQPLib\InputOutput;

use AMQPLib\Binary;
use AMQPLib\InputOutputInterface;
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
     * @param int|null $timeout in microseconds
     *
     * @return $this
     */
    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function open($host, $port)
    {
        if ($this->socket) {
            return $this;
        }

        $this->buffer = '';

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
                $this->address,
                socket_strerror(socket_last_error())
            ));
        }

        $this->logger->debug(sprintf('Connection established'), [
            'host' => $host,
            'port' => $port,
        ]);

        return $this;
    }

    /**
     * @return $this
     */
    public function close()
    {
        if ($this->socket) {
            $this->buffer = '';
            socket_close($this->socket);

            $this->logger->debug('Connection closed');
        }

        return $this;
    }

    /**
     * @param int $length
     *
     * @return string
     */
    public function read($length)
    {
        $received = Binary::length($this->buffer);

        while ($length > $received) {
            $data = socket_read($this->socket, $length - $received, PHP_BINARY_READ);

            if ($data === false) {
                throw new \RuntimeException(
                    'An error occur while reading from the socket: %s'.
                    socket_strerror(socket_last_error($this->socket))
                );
            }

            $received += Binary::length($data);
            $this->buffer .= $data;
        }

        $data = Binary::subset($this->buffer, 0, $length);

        $this->buffer = Binary::subset($this->buffer, $length);

        $this->logger->debug('Receive: '.Binary::render($data));

        return $data;
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

        $this->logger->debug('Sending: '.Binary::render($data));

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
