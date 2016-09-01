<?php

namespace ButterAMQP\Heartbeat;

use ButterAMQP\HeartbeatInterface;

class TimeHeartbeat implements HeartbeatInterface
{
    /**
     * @var int
     */
    private $heartbeatDelay;

    /**
     * @var int
     */
    private $lastServerBeat;

    /**
     * @var int
     */
    private $lastClientBeat;

    /**
     * @var callable|string
     */
    private $timeFunction;

    /**
     * @param int             $heartbeatDelay
     * @param callable|string $timeFunction
     */
    public function __construct($heartbeatDelay, $timeFunction = 'time')
    {
        $this->heartbeatDelay = $heartbeatDelay;
        $this->timeFunction = $timeFunction;
    }

    /**
     * {@inheritdoc}
     */
    public function serverBeat()
    {
        $this->lastServerBeat = $this->currentTime();
    }

    /**
     * {@inheritdoc}
     */
    public function clientBeat()
    {
        $this->lastClientBeat = $this->currentTime();
    }

    /**
     * {@inheritdoc}
     */
    public function shouldSendHeartbeat()
    {
        if ($this->heartbeatDelay == 0) {
            return false;
        }

        return $this->currentTime() - $this->lastClientBeat >= $this->heartbeatDelay;
    }

    /**
     * {@inheritdoc}
     */
    public function isServerHeartbeatMissing()
    {
        if ($this->heartbeatDelay == 0) {
            return false;
        }

        return $this->currentTime() - $this->lastServerBeat >= $this->heartbeatDelay * 1.5;
    }

    /**
     * @return int
     */
    private function currentTime()
    {
        return call_user_func($this->timeFunction);
    }
}
