<?php

namespace AMQLib;

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
        $this->length = $length === null ? Binary::length($data) : $length;
    }

    /**
     * @param Buffer $buffer
     *
     * @return $this
     */
    public function append(Buffer $buffer)
    {
        $this->write($buffer->fetch(), $buffer->size());

        return $this;
    }

    /**
     * @param string   $data
     * @param int|null $length
     *
     * @return $this
     */
    public function write($data, $length = null)
    {
        $this->data .= $data;
        $this->length += $length === null ? Binary::length($data) : $length;

        return $this;
    }

    /**
     * @param int $length
     *
     * @return string
     */
    public function read($length)
    {
        $buffer = Binary::subset($this->data, $this->offset, $length);
        $this->offset += $length;

        return $buffer;
    }

    /**
     * @return int
     */
    public function tell()
    {
        return $this->offset;
    }

    /**
     * @return int
     */
    public function size()
    {
        return $this->length;
    }

    /**
     * @return string
     */
    public function fetch()
    {
        return $this->data;
    }

    /**
     * @return string
     */
    public function eof()
    {
        return $this->offset >= $this->length;
    }
}
