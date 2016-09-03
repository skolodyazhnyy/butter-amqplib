<?php

namespace ButterAMQP\IO;

use ButterAMQP\Binary;
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
    public function read($length, $blocking = true, $timeout = null)
    {
        if (Binary::length($this->readBuffer) >= $length) {
            $data = Binary::subset($this->readBuffer, 0, $length);
            $this->readBuffer = Binary::subset($this->readBuffer, $length);

            return $data;
        }

        if ($blocking && $timeout == 0) {
            throw new \RuntimeException(sprintf(
                'There is not enough data in the reading buffer. Requested blocking reading with 0 timeout will never get resolved.'
            ));
        }

        // Not enough data, so pretend timeout or non-blocking reading
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function peek($length, $blocking = true, $timeout = null)
    {
        if (Binary::length($this->readBuffer) >= $length) {
            return Binary::subset($this->readBuffer, 0, $length);
        }

        if ($blocking && $timeout == 0) {
            throw new \RuntimeException(sprintf(
                'There is not enough data in the reading buffer. Requested blocking reading with 0 timeout will never get resolved.'
            ));
        }

        // Not enough data, so pretend timeout or non-blocking reading
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
            $data = Binary::subset($this->writeBuffer, 0, $length);
            $this->writeBuffer = Binary::subset($this->writeBuffer, $length);
        }

        return $data;
    }
}
