<?php

namespace AMQPLib;

Binary::init();

class Binary
{
    /**
     * Check if machine endian format is big endian format.
     *
     * @var bool
     */
    private static $isBigEndian = false;

    /**
     * Check if mb_ functions are available.
     *
     * @var bool
     */
    private static $isMultibyteAvailable = false;

    /**
     * Initialize flags.
     */
    public static function init()
    {
        self::$isBigEndian = unpack('S', "\x00\x01")[1] == 1;
        self::$isMultibyteAvailable = function_exists('mb_strlen');
    }

    /**
     * Unpack a value.
     *
     * @param string $format
     * @param string $value
     *
     * @return string
     */
    public static function pack($format, $value)
    {
        return pack($format, $value);
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

    /**
     * Renders binary data into a string.
     *
     * @param string   $data
     * @param int|null $length
     *
     * @return string
     */
    public static function render($data, $length = null)
    {
        if ($length === null) {
            $length = mb_strlen($data, 'ASCII');
        }

        $hex = [];

        for ($p = 0; $p < $length; ++$p) {
            $char = $data[$p];
            $hex[] = ord($char).(self::isPrintable($char) ? "[$char]" : '');
        }

        return implode(' ', $hex);
    }

    /**
     * @param string $data
     *
     * @return int
     */
    public static function length($data)
    {
        return self::$isMultibyteAvailable ? mb_strlen($data, 'ASCII') : strlen($data);
    }

    /**
     * @param string $data
     * @param int    $offset
     * @param int    $length
     *
     * @return int
     */
    public static function subset($data, $offset = 0, $length = null)
    {
        return self::$isMultibyteAvailable ? mb_substr($data, $offset, $length, 'ASCII') :
            substr($data, $offset, $length);
    }

    /**
     * @param string $char
     *
     * @return bool
     */
    private static function isPrintable($char)
    {
        return strpos(' !"#$%&\'()*+,-./0123456789:;<=>?@[]^_`abcdefghijklmnopqrstuvwxyz{|}~', strtolower($char)) !== false;
    }
}
