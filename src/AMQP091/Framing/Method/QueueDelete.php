<?php
/*
 * This file is automatically generated.
 */

namespace ButterAMQP\AMQP091\Framing\Method;

use ButterAMQP\AMQP091\Framing\Frame;
use ButterAMQP\Value;

/**
 * Delete a queue.
 *
 * @codeCoverageIgnore
 */
class QueueDelete extends Frame
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
    private $ifUnused;

    /**
     * @var bool
     */
    private $ifEmpty;

    /**
     * @var bool
     */
    private $noWait;

    /**
     * @param int    $channel
     * @param int    $reserved1
     * @param string $queue
     * @param bool   $ifUnused
     * @param bool   $ifEmpty
     * @param bool   $noWait
     */
    public function __construct($channel, $reserved1, $queue, $ifUnused, $ifEmpty, $noWait)
    {
        $this->reserved1 = $reserved1;
        $this->queue = $queue;
        $this->ifUnused = $ifUnused;
        $this->ifEmpty = $ifEmpty;
        $this->noWait = $noWait;

        parent::__construct($channel);
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
     * Delete only if unused.
     *
     * @return bool
     */
    public function isIfUnused()
    {
        return $this->ifUnused;
    }

    /**
     * Delete only if empty.
     *
     * @return bool
     */
    public function isIfEmpty()
    {
        return $this->ifEmpty;
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
        $data = "\x00\x32\x00\x28".
            Value\ShortValue::encode($this->reserved1).
            Value\ShortStringValue::encode($this->queue).
            Value\OctetValue::encode(($this->ifUnused ? 1 : 0) | (($this->ifEmpty ? 1 : 0) << 1) | (($this->noWait ? 1 : 0) << 2));

        return "\x01".pack('nN', $this->channel, strlen($data)).$data."\xCE";
    }
}
