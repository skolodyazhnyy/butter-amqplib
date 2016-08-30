<?php

namespace AMQPLibTest\Integration;

use AMQPLib\InputOutputInterface;

class RecorderDecoratedIO implements InputOutputInterface
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
    public function read($length)
    {
        $data = $this->io->read($length);

        $this->received .= $data;

        return $data;
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
