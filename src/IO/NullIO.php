<?php

namespace ButterAMQP\IO;

use ButterAMQP\IOInterface;

class NullIO implements IOInterface
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
    public function peek($length, $blocking = true)
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function read($length, $blocking = true)
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
