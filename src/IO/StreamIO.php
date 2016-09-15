<?php

namespace ButterAMQP\IO;

use ButterAMQP\Exception\IOClosedException;
use ButterAMQP\Exception\IOException;
use ButterAMQP\IOInterface;

class StreamIO implements IOInterface
{
    const DEFAULT_CONNECTION_TIMEOUT = 30;
    const DEFAULT_READING_TIMEOUT = 1;

    /**
     * @var resource|null
     */
    private $stream;

    /**
     * @var string
     */
    private $buffer;

    /**
     * {@inheritdoc}
     */
    public function open($protocol, $host, $port, array $parameters = [])
    {
        if ($this->isOpen()) {
            return $this;
        }

        $this->buffer = '';

        $this->stream = $this->openConnection(
            $protocol,
            $host,
            $port,
            $parameters
        );

        $this->tuneConnection($parameters);

        return $this;
    }

    /**
     * @param string $protocol
     * @param string $host
     * @param int    $port
     * @param array  $parameters
     *
     * @return resource
     *
     * @throws IOException
     */
    private function openConnection($protocol, $host, $port, array $parameters = [])
    {
        $timeout = isset($parameters['connection_timeout']) ?
            $parameters['connection_timeout'] : self::DEFAULT_CONNECTION_TIMEOUT;

        $stream = @stream_socket_client(
            sprintf('%s://%s:%d', $protocol, $host, $port),
            $errno,
            $errstr,
            $timeout,
            STREAM_CLIENT_CONNECT,
            $this->createStreamContext($parameters)
        );

        if (!$stream) {
            throw new IOException(sprintf('An error occur while connecting to "%s:%d": %s', $host, $port, $errstr));
        }

        return $stream;
    }

    /**
     * @param array $parameters
     *
     * @return resource
     */
    private function createStreamContext(array $parameters)
    {
        static $options = [
            'certfile' => 'local_cert',
            'keyfile' => 'local_pk',
            'cacertfile' => 'cafile',
            'passphrase' => 'passphrase',
            'verify' => 'verify_peer',
            'allow_self_signed' => 'allow_self_signed',
        ];

        $context = stream_context_create();

        foreach ($parameters as $name => $value) {
            if (!isset($options[$name])) {
                continue;
            }

            stream_context_set_option($context, 'ssl', $options[$name], $value);
        }

        return $context;
    }

    /**
     * @param array $parameters
     */
    private function tuneConnection(array $parameters)
    {
        $timeout = isset($parameters['timeout']) ? $parameters['timeout'] : self::DEFAULT_READING_TIMEOUT;

        list($sec, $usec) = explode('|', number_format($timeout, 6, '|', ''));

        stream_set_timeout($this->stream, $sec, $usec);
        stream_set_read_buffer($this->stream, 0);
        stream_set_write_buffer($this->stream, 0);
    }

    /**
     * @return $this
     */
    public function close()
    {
        if (!$this->stream) {
            return $this;
        }

        fclose($this->stream);

        $this->stream = null;

        return $this;
    }

    /**
     * @param string   $data
     * @param int|null $length
     *
     * @return $this
     *
     * @throws IOException
     */
    public function write($data, $length = null)
    {
        if ($length === null) {
            $length = strlen($data);
        }

        while ($length > 0) {
            if (!$this->isOpen()) {
                throw new IOClosedException('Connection is closed');
            }

            $written = @fwrite($this->stream, $data, $length);
            if ($written === false) {
                throw new IOException('An error occur while writing to socket');
            }

            $length -= $written;
            $data = $length ? substr($data, $written, $length) : '';
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function peek($length, $blocking = true)
    {
        $received = strlen($this->buffer);

        if ($received >= $length) {
            return $this->buffer;
        }

        $this->recv($length - $received, $blocking);

        if (strlen($this->buffer) >= $length) {
            return $this->buffer;
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function read($length, $blocking = true)
    {
        if ($this->peek($length, $blocking) === null) {
            return null;
        }

        $data = substr($this->buffer, 0, $length);

        $this->buffer = substr($this->buffer, $length, strlen($this->buffer) - $length);

        return $data;
    }

    /**
     * @param int  $length
     * @param bool $blocking
     *
     * @return string
     *
     * @throws IOException
     */
    private function recv($length, $blocking)
    {
        @stream_set_blocking($this->stream, $blocking);

        $pending = $length;

        do {
            if (!$this->isOpen()) {
                throw new IOClosedException('Connection is closed');
            }

            if (($received = fread($this->stream, $pending)) === false) {
                throw new IOException('An error occur while reading from the socket');
            }

            $pending -= strlen($received);
            $this->buffer .= $received;

            $meta = stream_get_meta_data($this->stream);

            if ($meta['timed_out']) {
                break;
            }
        } while ($pending > 0);
    }

    /**
     * @return bool
     */
    public function isOpen()
    {
        return is_resource($this->stream) && !feof($this->stream);
    }
}
