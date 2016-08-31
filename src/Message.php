<?php

namespace AMQLib;

class Message
{
    const FLAG_MANDATORY = 0b01;
    const FLAG_IMMEDIATE = 0b10;

    /**
     * @var array
     */
    private $properties;

    /**
     * @var int
     */
    private $size;

    /**
     * @var string
     */
    private $body;

    /**
     * @param string $body
     * @param array  $properties
     */
    public function __construct($body, array $properties)
    {
        $this->properties = $properties;
        $this->body = $body;
        $this->size = Binary::length($body);
    }

    /**
     * @return array
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @return int
     */
    public function getSize()
    {
        return $this->size;
    }
}
