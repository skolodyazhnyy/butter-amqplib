<?php

namespace ButterAMQP\IO;

use ButterAMQP\IOInterface;

class NullIO implements IOInterface
{
    /**
     * {@inheritdoc}
     */
    public function open($protocol, $host, $port, array $parameters = [])
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
