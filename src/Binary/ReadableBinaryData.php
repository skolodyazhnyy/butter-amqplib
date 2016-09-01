<?php

namespace ButterAMQP\Binary;

class ReadableBinaryData
{
    /**
     * @var string
     */
    private $label;

    /**
     * @var string
     */
    private $data;

    /**
     * @param string $label
     * @param string $data
     */
    public function __construct($label, $data)
    {
        $this->label = $label;
        $this->data = $data;
    }

    /**
     * Renders binary data into a string.
     *
     * @return string
     */
    public function __toString()
    {
        $length = mb_strlen($this->data, 'ASCII');

        $hex = [];

        for ($p = 0; $p < $length; ++$p) {
            $char = $this->data[$p];
            $hex[] = ord($char).(self::isPrintable($char) ? "[$char]" : '');
        }

        return ($this->label ? $this->label.' ' : '').$length.' bytes: '.implode(' ', $hex);
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
