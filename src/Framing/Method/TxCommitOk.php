<?php
/*
 * This file is automatically generated.
 */

namespace ButterAMQP\Framing\Method;

use ButterAMQP\Framing\Frame;

/**
 * Confirm a successful commit.
 *
 * @codeCoverageIgnore
 */
class TxCommitOk extends Frame
{
    /**
     * @return string
     */
    public function encode()
    {
        return "\x01".pack('n', $this->channel)."\x00\x00\x00\x04\x00\x5A\x00\x15\xCE";
    }
}
