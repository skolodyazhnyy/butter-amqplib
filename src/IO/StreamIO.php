<?php

namespace ButterAMQP\IO;

use ButterAMQP\Binary;
use ButterAMQP\Exception\IOClosedException;
use ButterAMQP\Exception\IOException;
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
     * @var int|float
     */
    private $connectionTimeout;

    /**
     * @var int|float
     */
    private $readingTimeout;

    /**
     * Initialize default logger.
     *
     * @param int|float $connectionTimeout
     * @param int|float $readingTimeout
     */
    public function __construct($connectionTimeout = 30, $readingTimeout = 1)
    {
        $this->logger = new NullLogger();

        $this->setConnectionTimeout($connectionTimeout);
        $this->setReadingTimeout($readingTimeout);
    }

    /**
     * {@inheritdoc}
     */
    public function open($protocol, $host, $port, array $parameters = [])
    {
        if ($this->stream && $this->isOpen()) {
            return $this;
        }

        $this->logger->debug(sprintf('Connecting to "%s://%s:%d"...', $protocol, $host, $port), [
            'protocol' => $protocol,
            'host' => $host,
            'port' => $port,
            'parameters' => $parameters,
        ]);

        $context = $this->createStreamContext($parameters);

        $this->stream = @stream_socket_client(
            sprintf('%s://%s:%d', $protocol, $host, $port),
            $errno,
            $errstr,
            $this->connectionTimeout,
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

        $this->logger->debug(sprintf('Connection established'), [
            'host' => $host,
            'port' => $port,
        ]);

        $this->buffer = '';

        $this->applyReadingTimeout();

        return $this;
    }

    /**
     * @param float|int $timeout
     *
     * @return $this
     */
    public function setReadingTimeout($timeout)
    {
        $this->readingTimeout = $timeout;

        if ($this->stream) {
            $this->applyReadingTimeout();
        }

        return $this;
    }

    /**
     * @param float|int $connectionTimeout
     *
     * @return $this
     */
    public function setConnectionTimeout($connectionTimeout)
    {
        $this->connectionTimeout = $connectionTimeout;

        return $this;
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

        $this->logger->debug('Connection closed');

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
            $length = Binary::length($data);
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
            $data = $length ? Binary::subset($data, $written, $length) : '';
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function peek($length, $blocking = true)
    {
        $received = Binary::length($this->buffer);

        if ($received >= $length) {
            return $this->buffer;
        }

        $this->buffer .= $this->recv($length - $received, $blocking);

        if (Binary::length($this->buffer) >= $length) {
            return $this->buffer;
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function read($length, $blocking = true)
    {
        if (!$this->peek($length, $blocking)) {
            return null;
        }

        $data = Binary::subset($this->buffer, 0, $length);

        $this->buffer = Binary::subset($this->buffer, $length);

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

        stream_set_blocking($this->stream, $blocking);

        if (($received = fread($this->stream, $length)) === false) {
            throw new IOException('An error occur while reading from the socket');
        }

        if ($received) {
            $this->logger->debug(new ReadableBinaryData('Receive', $received));
        }

        return $received;
    }

    /**
     * Apply reading timeout to active stream.
     */
    private function applyReadingTimeout()
    {
        list($sec, $usec) = explode('|', number_format($this->readingTimeout, 6, '|', ''));

        stream_set_timeout($this->stream, $sec, $usec);
    }

    /**
     * @return bool
     */
    public function isOpen()
    {
        return is_resource($this->stream) && feof($this->stream);
    }

    /**
     * @param array $parameters
     *
     * @return resource
     */
    protected function createStreamContext(array $parameters)
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
}
