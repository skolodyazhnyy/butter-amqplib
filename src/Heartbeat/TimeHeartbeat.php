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
     * @var float
     */
    private $serverBeatFactor = 1.5;

    /**
     * @var float
     */
    private $clientBeatFactor = 0.75;

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
     * Heartbeat factor for server beats. This is multiplier for heartbeat delay when waiting for server heartbeat.
     *
     * Possible values:
     *  - Value of 1 means server connection will be considered dead if heartbeat didn't arrive in exact time of heartbeat.
     *  - Value less than 1 would mean heartbeat would be expected in less than heartbeat delay, which does not make
     *    much sense.
     *  - Value more than 1 would mean we allow extra delay for server heartbeats, for example factor of 2 would mean
     *    we allow server to delay heartbeat up to double of its expected time.
     *
     * @param float $serverBeatFactor
     *
     * @return $this
     */
    public function setServerBeatFactor($serverBeatFactor)
    {
        $this->serverBeatFactor = $serverBeatFactor;

        return $this;
    }

    /**
     * Heartbeat factor for client beats. This is multiplier for heartbeat delay when deciding to send heartbeat.
     *
     * Possible values:
     *  - Value of 1 means heartbeat would be sent after exactly time of heartbeat delay.
     *  - Value less than 1 would mean heartbeat would be send more frequently than expected. It is a good idea to send
     *    heartbeats a bit more frequently.
     *  - Value more than 1 would mean heartbeat would be send less frequently than expected. This may cause server to
     *    close connection because heartbeats will be delayed.
     *
     * @param float $clientBeatFactor
     *
     * @return $this
     */
    public function setClientBeatFactor($clientBeatFactor)
    {
        $this->clientBeatFactor = $clientBeatFactor;

        return $this;
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

        return $this->currentTime() - $this->lastClientBeat >= $this->heartbeatDelay * $this->clientBeatFactor;
    }

    /**
     * {@inheritdoc}
     */
    public function isServerHeartbeatMissing()
    {
        if ($this->heartbeatDelay == 0) {
            return false;
        }

        return $this->currentTime() - $this->lastServerBeat >= $this->heartbeatDelay * $this->serverBeatFactor;
    }

    /**
     * @return int
     */
    private function currentTime()
    {
        return call_user_func($this->timeFunction);
    }
}
