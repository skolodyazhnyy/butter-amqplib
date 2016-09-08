<?php

namespace ButterAMQP\Framing;

/**
 * @codeCoverageIgnore
 */
class Heartbeat extends Frame
{
    /**
     * @return string
     */
    public function encode()
    {
        return "\x08".pack('n', $this->channel)."\x00\x00\x00\x00\xCE";
    }
}
