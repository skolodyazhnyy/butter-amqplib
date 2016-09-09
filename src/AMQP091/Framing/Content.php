<?php

namespace ButterAMQP\AMQP091\Framing;

/**
 * @codeCoverageIgnore
 */
class Content extends Frame
{
    /**
     * @var string
     */
    private $data;

    /**
     * @param int    $channel
     * @param string $data
     */
    public function __construct($channel, $data)
    {
        $this->data = $data;

        parent::__construct($channel);
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
        return "\x03".pack('nN', $this->channel, strlen($this->data)).$this->data."\xCE";
    }
}
