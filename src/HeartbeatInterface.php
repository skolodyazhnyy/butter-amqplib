<?php

namespace ButterAMQP;

interface HeartbeatInterface
{
    /**
     * Register interaction from the server.
     */
    public function serverBeat();

    /**
     * Register interaction to the server.
     */
    public function clientBeat();

    /**
     * Check if its time to send heartbeat to the server.
     *
     * @return bool
     */
    public function shouldSendHeartbeat();

    /**
     * Check if servers heartbeat is missing.
     *
     * @return bool
     */
    public function isServerHeartbeatMissing();
}
