<?php

namespace AMQLibTest\Integration;

use AMQLib\InputOutputInterface;

class RecorderDecoratedInputOutput implements InputOutputInterface
{
    /**
     * @var InputOutputInterface
     */
    private $io;

    /**
     * @var string
     */
    private $sent;

    /**
     * @var string
     */
    private $received;

    /**
     * @param InputOutputInterface $io
     */
    public function __construct(InputOutputInterface $io)
    {
        $this->io = $io;
    }

    /**
     * {@inheritdoc}
     */
    public function open($host, $port)
    {
        $this->io->open($host, $port);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        $this->io->close();

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function read($length, $blocking = true, $timeout = null)
    {
        $data = $this->io->read($length, $blocking, $timeout);

        $this->received .= $data;

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function peek($length, $blocking = true, $timeout = null)
    {
        return $this->io->peek($length, $blocking, $timeout);
    }

    /**
     * {@inheritdoc}
     */
    public function write($data, $length = null)
    {
        $this->sent .= $data;

        $this->io->write($data, $length);

        return $this;
    }

    /**
     * @return string
     */
    public function getSent()
    {
        return $this->sent;
    }

    /**
     * @return string
     */
    public function getReceived()
    {
        return $this->received;
    }
}
