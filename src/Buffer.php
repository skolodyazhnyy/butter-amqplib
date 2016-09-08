<?php

namespace ButterAMQP;

/**
 * Simple reading buffered data stream provide simple API to read data byte by byte.
 */
class Buffer
{
    /**
     * @var int
     */
    private $offset = 0;

    /**
     * @var int
     */
    private $length;

    /**
     * @var string
     */
    private $data;

    /**
     * @param string   $data
     * @param int|null $length
     */
    public function __construct($data = '', $length = null)
    {
        $this->data = $data;
        $this->length = $length === null ? strlen($data) : $length;
    }

    /**
     * Return $length bytes and move offset.
     *
     * @param int $length
     *
     * @return string
     */
    public function read($length)
    {
        $buffer = substr($this->data, $this->offset, $length);
        $this->offset += $length;

        return $buffer;
    }

    /**
     * Return buffer total size.
     *
     * @return int
     */
    public function size()
    {
        return $this->length;
    }

    /**
     * Check if offset reached end of the buffer.
     *
     * @return string
     */
    public function eof()
    {
        return $this->offset >= $this->length;
    }
}
