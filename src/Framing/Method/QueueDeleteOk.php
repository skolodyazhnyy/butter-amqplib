<?php
/*
 * This file is automatically generated.
 */

namespace AMQLib\Framing\Method;

use AMQLib\Buffer;
use AMQLib\Framing\Method;
use AMQLib\Value;

/**
 * Confirm deletion of a queue.
 *
 * @codeCoverageIgnore
 */
class QueueDeleteOk extends Method
{
    /**
     * @var int
     */
    private $messageCount;

    /**
     * @param int $messageCount
     */
    public function __construct($messageCount)
    {
        $this->messageCount = $messageCount;
    }

    /**
     * MessageCount.
     *
     * @return int
     */
    public function getMessageCount()
    {
        return $this->messageCount;
    }

    /**
     * @return string
     */
    public function encode()
    {
        return "\x00\x32\x00\x29".
            Value\LongValue::encode($this->messageCount);
    }

    /**
     * @param Buffer $data
     *
     * @return $this
     */
    public static function decode(Buffer $data)
    {
        return new self(
            Value\LongValue::decode($data)
        );
    }
}
