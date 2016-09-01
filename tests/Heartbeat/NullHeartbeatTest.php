<?php

namespace AMQLibTest\Heartbeat;

use AMQLib\Heartbeat\NullHeartbeat;
use PHPUnit\Framework\TestCase;

class NullHeartbeatTest extends TestCase
{
    /**
     * @var NullHeartbeat
     */
    private $heartbeat;

    protected function setUp()
    {
        $this->heartbeat = new NullHeartbeat();
    }

    /**
     * Null heartbeat server beat should not cause any side effects.
     */
    public function testServerBeat()
    {
        self::assertNull($this->heartbeat->serverBeat());
    }

    /**
     * Null heartbeat client beat should not cause any side effects.
     */
    public function testClientBeat()
    {
        self::assertNull($this->heartbeat->clientBeat());
    }

    /**
     * Null heartbeat should not expect any heartbeat from the server.
     */
    public function testIsServerHeartbeatMissing()
    {
        self::assertFalse($this->heartbeat->isServerHeartbeatMissing());
    }

    /**
     * Null heartbeat should not request to send heartbeat.
     */
    public function testShouldSendHeartbeat()
    {
        self::assertFalse($this->heartbeat->shouldSendHeartbeat());
    }
}
