<?php

namespace ButterAMQP\IO;

use ButterAMQP\Exception\IOClosedException;
use ButterAMQP\Exception\IOException;
use ButterAMQP\IOInterface;
use ButterAMQP\Debug\ReadableBinaryData;
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
     * @var int
     */
    private $readAheadSize;

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
    public function open($protocol, $host, $port, array $parameters = [])
    {
        if ($this->stream && $this->isOpen()) {
            return $this;
        }

        $context = $this->createStreamContext($parameters);

        $this->stream = @stream_socket_client(
            sprintf('%s://%s:%d', $protocol, $host, $port),
            $errno,
            $errstr,
            isset($parameters['connection_timeout']) ? $parameters['connection_timeout'] : 30,
            STREAM_CLIENT_CONNECT,
            $context
        );

        if (!$this->stream) {
            throw new IOException(sprintf(
                'Unable to connect to "%s:%d" using stream socket: %s',
                $host,
                $port,
                $errstr
            ));
        }

        $this->buffer = '';

        if (isset($parameters['timeout'])) {
            list($sec, $usec) = explode('|', number_format($parameters['timeout'], 6, '|', ''));
            stream_set_timeout($this->stream, $sec, $usec);
        }

        if (isset($parameters['read_ahead'])) {
            $this->readAheadSize = $parameters['read_ahead'];
        }

        return $this;
    }

    /**
     * @param array $parameters
     *
     * @return resource
     */
    private function createStreamContext(array $parameters)
    {
        $context = stream_context_create();

        if (isset($parameters['certfile'])) {
            stream_context_set_option($context, 'ssl', 'local_cert', $parameters['certfile']);
        }

        if (isset($parameters['keyfile'])) {
            stream_context_set_option($context, 'ssl', 'local_pk', $parameters['keyfile']);
        }

        if (isset($parameters['cacertfile'])) {
            stream_context_set_option($context, 'ssl', 'cafile', $parameters['cacertfile']);
        }

        if (isset($parameters['passphrase'])) {
            stream_context_set_option($context, 'ssl', 'passphrase', $parameters['passphrase']);
        }

        if (isset($parameters['verify'])) {
            stream_context_set_option($context, 'ssl', 'verify_peer', (bool) $parameters['verify']);
        }

        if (isset($parameters['allow_self_signed'])) {
            stream_context_set_option($context, 'ssl', 'allow_self_signed', (bool) $parameters['allow_self_signed']);
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
        if ($this->stream === null) {
            throw new IOClosedException('Connection is not open');
        }

        if ($length === null) {
            $length = strlen($data);
        }

        $this->logger->debug(new ReadableBinaryData('Sending', $data));

        while ($length > 0) {
            if ($this->isOpen()) {
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
        if ($this->stream === null) {
            throw new IOClosedException('Connection is not open');
        }

        if ($this->isOpen()) {
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
        return is_resource($this->stream) && feof($this->stream);
    }
}
