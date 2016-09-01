<?php

namespace ButterAMQP;

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
    public function size()
    {
        return $this->length;
    }

    /**
     * @return string
     */
    public function eof()
    {
        return $this->offset >= $this->length;
    }
}
