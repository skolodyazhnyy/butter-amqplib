<?php

namespace ButterAMQP;

class Message
{
    const FLAG_MANDATORY = 0b01;
    const FLAG_IMMEDIATE = 0b10;

    /**
     * @var string
     */
    private $body;

    /**
     * @var array
     */
    private $properties = [];

    /**
     * @param string $body
     * @param array  $properties
     */
    public function __construct($body, array $properties = [])
    {
        $this->body = $body;
        $this->properties = $properties;
    }

    /**
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @return array
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasProperty($name)
    {
        return isset($this->properties[$name]);
    }

    /**
     * @param string $name
     * @param mixed  $default
     *
     * @return mixed
     */
    public function getProperty($name, $default = null)
    {
        return $this->hasProperty($name) ? $this->properties[$name] : $default;
    }

    /**
     * @param string $name
     * @param mixed  $value
     *
     * @return Message
     */
    public function withProperty($name, $value)
    {
        return $this->withProperties([$name => $value]);
    }

    /**
     * @param string $name
     *
     * @return Message
     */
    public function withoutProperty($name)
    {
        $properties = $this->properties;

        unset($properties[$name]);

        return new self($this->body, $properties);
    }

    /**
     * @param array $properties
     *
     * @return Message
     */
    public function withProperties(array $properties)
    {
        return new self($this->body, array_merge($this->properties, $properties));
    }

    /**
     * @return array
     */
    public function getHeaders()
    {
        return $this->getProperty('headers', []);
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasHeader($name)
    {
        $headers = $this->getHeaders();

        return isset($headers[$name]);
    }

    /**
     * @param string $name
     * @param mixed  $default
     *
     * @return mixed
     */
    public function getHeader($name, $default = null)
    {
        $headers = $this->getHeaders();

        return isset($headers[$name]) ? $headers[$name] : $default;
    }

    /**
     * @param string $name
     * @param mixed  $value
     *
     * @return Message
     */
    public function withHeader($name, $value)
    {
        return $this->withHeaders([$name => $value]);
    }

    /**
     * @param string $name
     *
     * @return Message
     */
    public function withoutHeader($name)
    {
        $headers = $this->getHeaders();

        unset($headers[$name]);

        $properties = $this->properties;
        $properties['headers'] = $headers;

        return new self($this->body, $properties);
    }

    /**
     * @param array $headers
     *
     * @return Message
     */
    public function withHeaders(array $headers)
    {
        $headers = array_merge($this->getHeaders(), $headers);

        return $this->withProperty('headers', $headers);
    }

    /**
     * Define how to print object when dumping.
     *
     * @return array
     */
    public function __debugInfo()
    {
        return [
            'properties' => $this->getProperties(),
            'body' => $this->getBody(),
        ];
    }
}
