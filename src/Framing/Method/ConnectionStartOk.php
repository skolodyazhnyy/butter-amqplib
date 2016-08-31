<?php
/*
 * This file is automatically generated.
 */

namespace AMQLib\Framing\Method;

use AMQLib\Buffer;
use AMQLib\Framing\Method;
use AMQLib\Value;

/**
 * Select security mechanism and locale.
 */
class ConnectionStartOk extends Method
{
    /**
     * @var array
     */
    private $clientProperties = [];

    /**
     * @var string
     */
    private $mechanism;

    /**
     * @var string
     */
    private $response;

    /**
     * @var string
     */
    private $locale;

    /**
     * @param array  $clientProperties
     * @param string $mechanism
     * @param string $response
     * @param string $locale
     */
    public function __construct($clientProperties, $mechanism, $response, $locale)
    {
        $this->clientProperties = $clientProperties;
        $this->mechanism = $mechanism;
        $this->response = $response;
        $this->locale = $locale;
    }

    /**
     * Client properties.
     *
     * @return array
     */
    public function getClientProperties()
    {
        return $this->clientProperties;
    }

    /**
     * Selected security mechanism.
     *
     * @return string
     */
    public function getMechanism()
    {
        return $this->mechanism;
    }

    /**
     * Security response data.
     *
     * @return string
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Selected message locale.
     *
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * @return string
     */
    public function encode()
    {
        return "\x00\x0A\x00\x0B".
            Value\TableValue::encode($this->clientProperties).
            Value\ShortStringValue::encode($this->mechanism).
            Value\LongStringValue::encode($this->response).
            Value\ShortStringValue::encode($this->locale);
    }

    /**
     * @param Buffer $data
     *
     * @return $this
     */
    public static function decode(Buffer $data)
    {
        return new self(
            Value\TableValue::decode($data),
            Value\ShortStringValue::decode($data),
            Value\LongStringValue::decode($data),
            Value\ShortStringValue::decode($data)
        );
    }
}
