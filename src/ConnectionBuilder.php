<?php

namespace ButterAMQP;

use ButterAMQP\AMQP091\Connection;
use ButterAMQP\Debug\LoggerDecoratedWire;
use ButterAMQP\Debug\LoggerDecoratedIO;
use ButterAMQP\IO\StreamIO;
use ButterAMQP\AMQP091\Wire;
use Psr\Log\LoggerInterface;

class ConnectionBuilder
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @return ConnectionBuilder
     */
    public static function make()
    {
        return new self();
    }

    /**
     * Sets a logger.
     *
     * @param LoggerInterface $logger
     *
     * @return $this
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * @param Url|string|array $url
     *
     * @return ConnectionInterface
     */
    public function create($url)
    {
        if (is_string($url)) {
            $url = Url::parse($url);
        }

        if (is_array($url)) {
            $url = Url::import($url);
        }

        if (!$url instanceof Url) {
            throw new \InvalidArgumentException(sprintf('URL should be a string, an array or an instance of Url class'));
        }

        $io = new StreamIO();

        if ($this->logger) {
            $io = new LoggerDecoratedIO($io, $this->logger);
        }

        $wire = new Wire($io);

        if ($this->logger) {
            $wire = new LoggerDecoratedWire($wire, $this->logger);
        }

        return new Connection($url, $wire);
    }
}
