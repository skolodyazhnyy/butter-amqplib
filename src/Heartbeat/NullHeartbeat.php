<?php

namespace ButterAMQP\Heartbeat;

use ButterAMQP\HeartbeatInterface;

class NullHeartbeat implements HeartbeatInterface
{
    /**
     * {@inheritdoc}
     */
    public function serverBeat()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function clientBeat()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function shouldSendHeartbeat()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function isServerHeartbeatMissing()
    {
        return false;
    }
}
