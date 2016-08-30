<?php

namespace AMQPLib\Framing;

use AMQPLib\Buffer;

class Content extends Frame
{
    /**
     * @var string
     */
    private $data;

    /**
     * @param string $data
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * @return string
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @return string
     */
    public function encode()
    {
        return $this->data;
    }

    /**
     * @param Buffer $data
     *
     * @return Content
     */
    public static function decode(Buffer $data)
    {
        return new self($data->read($data->size()));
    }

    /**
     * @return string
     */
    public function getFrameType()
    {
        return "\x03";
    }
}
