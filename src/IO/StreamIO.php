<?php

namespace ButterAMQP\IO;

use ButterAMQP\Exception\IOClosedException;
use ButterAMQP\Exception\IOException;
use ButterAMQP\IOInterface;

class StreamIO implements IOInterface
{
    /**
     * @var resource|null
     */
    private $stream;

    /**
     * @var string
     */
    private $buffer;

    /**
     * @var int
     */
    private $readAheadSize;

    /**
     * {@inheritdoc}
     */
    public function open($protocol, $host, $port, array $parameters = [])
    {
        if ($this->isOpen()) {
            return $this;
        }

        $this->buffer = '';

        $parameters = array_merge(['connection_timeout' => 30, 'read_ahead' => 13000, 'timeout' => 1], $parameters);

        $this->stream = @stream_socket_client(
            sprintf('%s://%s:%d', $protocol, $host, $port),
            $errno,
            $errstr,
            $parameters['connection_timeout'],
            STREAM_CLIENT_CONNECT,
            $this->createStreamContext($parameters)
        );

        if (!$this->stream) {
            throw new IOException(sprintf('An error occur while connecting to "%s:%d": %s', $host, $port, $errstr));
        }

        $this->setReadingTimeout($parameters['timeout']);
        $this->setReadAheadSize($parameters['read_ahead']);

        return $this;
    }

    /**
     * @param float $timeout
     */
    private function setReadingTimeout($timeout)
    {
        list($sec, $usec) = explode('|', number_format($timeout, 6, '|', ''));
        stream_set_timeout($this->stream, $sec, $usec);
    }

    /**
     * @param int $size
     */
    private function setReadAheadSize($size)
    {
        $this->readAheadSize = $size;
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

        $this->buffer .= $this->recv($length - $received, $blocking);

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
        if (!$this->isOpen()) {
            throw new IOClosedException('Connection is closed');
        }

        if ($this->readAheadSize) {
            $meta = stream_get_meta_data($this->stream);

            if ($length < $meta['unread_bytes']) {
                $length = min($this->readAheadSize, $meta['unread_bytes']);
            }
        }

        stream_set_blocking($this->stream, $blocking);

        if (($received = fread($this->stream, $length)) === false) {
            throw new IOException('An error occur while reading from the socket');
        }

        return $received;
    }

    /**
     * @return bool
     */
    public function isOpen()
    {
        return is_resource($this->stream) && !feof($this->stream);
    }
}
