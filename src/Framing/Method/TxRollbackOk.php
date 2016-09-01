<?php
/*
 * This file is automatically generated.
 */

namespace ButterAMQP\Framing\Method;

use ButterAMQP\Buffer;
use ButterAMQP\Framing\Method;

/**
 * Confirm successful rollback.
 *
 * @codeCoverageIgnore
 */
class TxRollbackOk extends Method
{
    /**
     * @return string
     */
    public function encode()
    {
        return "\x00\x5A\x00\x1F";
    }

    /**
     * @param Buffer $data
     *
     * @return $this
     */
    public static function decode(Buffer $data)
    {
        return new self();
    }
}
