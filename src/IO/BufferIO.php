<?php

namespace ButterAMQP\IO;

use ButterAMQP\IOInterface;

class BufferIO implements IOInterface
{
    /**
     * @var string
     */
    private $writeBuffer;

    /**
     * @var string
     */
    private $readBuffer;

    /**
     * @param string $readBuffer
     */
    public function __construct($readBuffer = '')
    {
        $this->readBuffer = $readBuffer;
    }

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
        if (strlen($this->readBuffer) >= $length) {
            $data = substr($this->readBuffer, 0, $length);
            $this->readBuffer = substr($this->readBuffer, $length, strlen($this->readBuffer) - $length);

            return $data;
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function peek($length, $blocking = true)
    {
        if (strlen($this->readBuffer) >= $length) {
            return substr($this->readBuffer, 0, $length);
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function write($data, $length = null)
    {
        $this->writeBuffer .= $data;

        return $this;
    }

    /**
     * @param string $data
     *
     * @return $this
     */
    public function push($data)
    {
        $this->readBuffer .= $data;

        return $this;
    }

    /**
     * @param null $length
     *
     * @return string
     */
    public function pop($length = null)
    {
        if ($length === null) {
            $data = $this->writeBuffer;
            $this->writeBuffer = '';
        } else {
            $data = substr($this->writeBuffer, 0, $length);
            $this->writeBuffer = substr($this->writeBuffer, $length, strlen($this->writeBuffer) - $length);
        }

        return $data;
    }
}
