<?php

namespace ButterAMQP;

Binary::init();

/**
 * Simple set of static methods to polyfill some PHP functions.
 */
class Binary
{
    /**
     * Check if machine endian format is big endian format.
     *
     * @var bool
     */
    private static $isBigEndian = false;

    /**
     * Initialize flags.
     */
    public static function init()
    {
        self::$isBigEndian = unpack('S', "\x00\x01")[1] == 1;
    }

    /**
     * Pack a value and enforce big endian format.
     *
     * @param string $format
     * @param string $value
     *
     * @return string
     */
    public static function packbe($format, $value)
    {
        return self::$isBigEndian ? pack($format, $value) : strrev(pack($format, $value));
    }

    /**
     * Unpack a value.
     *
     * @param string $format
     * @param string $data
     *
     * @return string
     */
    public static function unpack($format, $data)
    {
        return unpack($format, $data)[1];
    }

    /**
     * Unpack a value and enforce big endian format.
     *
     * @param string $format
     * @param string $data
     *
     * @return string
     */
    public static function unpackbe($format, $data)
    {
        return self::$isBigEndian ? unpack($format, $data)[1] : unpack($format, strrev($data))[1];
    }
}
