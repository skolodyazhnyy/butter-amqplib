<?php

namespace AMQLib\InputOutput;

use AMQLib\InputOutputInterface;

class NullInputOutput implements InputOutputInterface
{
    /**
     * {@inheritdoc}
     */
    public function open($host, $port)
    {
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function peek($length, $blocking = true, $timeout = null)
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function read($length, $blocking = true, $timeout = null)
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function write($data, $length = null)
    {
        return $this;
    }
}
