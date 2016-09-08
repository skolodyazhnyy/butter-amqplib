<?php
/*
 * This file is automatically generated.
 */

namespace ButterAMQP\Framing\Method;

use ButterAMQP\Framing\Frame;

/**
 * Confirm recovery.
 *
 * @codeCoverageIgnore
 */
class BasicRecoverOk extends Frame
{
    /**
     * @return string
     */
    public function encode()
    {
        return "\x01".pack('n', $this->channel)."\x00\x00\x00\x04\x00\x3C\x00\x6F\xCE";
    }
}
