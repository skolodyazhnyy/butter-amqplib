<?php
/*
 * This file is automatically generated.
 */

namespace AMQLib\Framing\Method;

use AMQLib\Buffer;
use AMQLib\Framing\Method;
use AMQLib\Value;

/**
 * Purge a queue.
 */
class QueuePurge extends Method
{
    /**
     * @var int
     */
    private $reserved1;

    /**
     * @var string
     */
    private $queue;

    /**
     * @var bool
     */
    private $noWait;

    /**
     * @param int    $reserved1
     * @param string $queue
     * @param bool   $noWait
     */
    public function __construct($reserved1, $queue, $noWait)
    {
        $this->reserved1 = $reserved1;
        $this->queue = $queue;
        $this->noWait = $noWait;
    }

    /**
     * Reserved1.
     *
     * @return int
     */
    public function getReserved1()
    {
        return $this->reserved1;
    }

    /**
     * Queue.
     *
     * @return string
     */
    public function getQueue()
    {
        return $this->queue;
    }

    /**
     * NoWait.
     *
     * @return bool
     */
    public function isNoWait()
    {
        return $this->noWait;
    }

    /**
     * @return string
     */
    public function encode()
    {
        return "\x00\x32\x00\x1E".
            Value\ShortValue::encode($this->reserved1).
            Value\ShortStringValue::encode($this->queue).
            Value\BooleanValue::encode($this->noWait);
    }

    /**
     * @param Buffer $data
     *
     * @return $this
     */
    public static function decode(Buffer $data)
    {
        return new self(
            Value\ShortValue::decode($data),
            Value\ShortStringValue::decode($data),
            Value\BooleanValue::decode($data)
        );
    }
}
