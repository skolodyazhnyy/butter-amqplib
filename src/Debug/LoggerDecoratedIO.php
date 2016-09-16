<?php

namespace ButterAMQP\Debug;

use ButterAMQP\IOInterface;
use Psr\Log\LoggerInterface;

class LoggerDecoratedIO implements IOInterface
{
    /**
     * @var IOInterface
     */
    private $io;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param IOInterface     $io
     * @param LoggerInterface $logger
     */
    public function __construct(IOInterface $io, LoggerInterface $logger)
    {
        $this->io = $io;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function open($protocol, $host, $port, array $parameters = [])
    {
        $this->logger->debug(sprintf('Connecting to "%s://%s:%d"...', $protocol, $host, $port), [
            'protocol' => $protocol,
            'host' => $host,
            'port' => $port,
            'parameters' => $parameters,
        ]);

        return $this->io->open($protocol, $host, $port, $parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        $this->logger->debug('Closing connection');

        return $this->io->close();
    }

    /**
     * {@inheritdoc}
     */
    public function read($length, $blocking = true)
    {
        $data = $this->io->read($length, $blocking);

        $this->logger->debug(new ReadableBinaryData('Reading', $data));

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function write($data, $length = null)
    {
        $this->logger->debug(new ReadableBinaryData('Writing', $data));

        return $this->io->write($data, $length);
    }
}
