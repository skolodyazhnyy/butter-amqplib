<?php

namespace ButterAMQPTest\Heartbeat;

use ButterAMQP\AMQP091\Framing\Heartbeat;
use ButterAMQP\Heartbeat\TimeHeartbeat;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;

class TimeHeartbeatTest extends TestCase
{
    /**
     * @var TimeHeartbeat
     */
    private $heartbeat;

    /**
     * @var Mock|callable
     */
    private $time;

    protected function setUp()
    {
        $this->time = $this->getMockBuilder(\stdClass::class)
            ->setMethods(['__invoke'])
            ->getMock();

        $this->heartbeat = new TimeHeartbeatMock(10, $this->time);
    }

    /**
     * Client heartbeat should be send after $delay seconds with client beat factor 1.
     */
    public function testShouldRequireHeartbeat()
    {
        $this->time->expects(self::atLeastOnce())
            ->method('__invoke')
            ->willReturnOnConsecutiveCalls(100, 110);

        $this->heartbeat->clientBeat();
        $this->heartbeat->setClientBeatFactor(1);

        self::assertTrue($this->heartbeat->shouldSendHeartbeat());
    }

    /**
     * Client heartbeat should be send even before $delay seconds with client beat factor less than 1.
     */
    public function testShouldRequireEarlyHeartbeatForLessThanOneFactor()
    {
        $this->time->expects(self::atLeastOnce())
            ->method('__invoke')
            ->willReturnOnConsecutiveCalls(100, 105);

        $this->heartbeat->clientBeat();
        $this->heartbeat->setClientBeatFactor(0.5);

        self::assertTrue($this->heartbeat->shouldSendHeartbeat());
    }

    /**
     * Client heartbeat should not be send before delay.
     */
    public function testShouldNotRequireHeartbeatTooOften()
    {
        $this->time->expects(self::atLeastOnce())
            ->method('__invoke')
            ->willReturnOnConsecutiveCalls(100, 109);

        $this->heartbeat->clientBeat();
        $this->heartbeat->setClientBeatFactor(1);

        self::assertFalse($this->heartbeat->shouldSendHeartbeat());
    }

    /**
     * Client heartbeat should not be send when disabled.
     */
    public function testShouldNotSendHeartbeatWhenDisabled()
    {
        $heartbeat = new TimeHeartbeatMock(0, $this->time);

        $this->time->expects(self::atLeastOnce())
            ->method('__invoke')
            ->willReturnOnConsecutiveCalls(100, 110);

        $heartbeat->clientBeat();
        $this->heartbeat->setClientBeatFactor(1);

        self::assertFalse($heartbeat->shouldSendHeartbeat());
    }

    /**
     * Server heartbeat should be considered missing if not received within $delay seconds.
     */
    public function testServerHeartbeatMissing()
    {
        $this->time->expects(self::atLeastOnce())
            ->method('__invoke')
            ->willReturnOnConsecutiveCalls(100, 111);

        $this->heartbeat->serverBeat();
        $this->heartbeat->setServerBeatFactor(1);

        self::assertTrue($this->heartbeat->isServerHeartbeatMissing());
    }

    /**
     * Server heartbeat should be considered missing if not received in 2 * $delay seconds for server beat factor 2.
     */
    public function testServerHeartbeatNotMissingWithFactorTwo()
    {
        $this->time->expects(self::atLeastOnce())
            ->method('__invoke')
            ->willReturnOnConsecutiveCalls(100, 120);

        $this->heartbeat->serverBeat();
        $this->heartbeat->setServerBeatFactor(2);

        self::assertFalse($this->heartbeat->isServerHeartbeatMissing());
    }

    /**
     * Server heartbeat should not be considered missing if received in less than $delay seconds.
     */
    public function testServerHeartbeatNotMissing()
    {
        $this->time->expects(self::atLeastOnce())
            ->method('__invoke')
            ->willReturnOnConsecutiveCalls(100, 110);

        $this->heartbeat->serverBeat();
        $this->heartbeat->setServerBeatFactor(1);

        self::assertFalse($this->heartbeat->isServerHeartbeatMissing());
    }

    /**
     * Server heartbeat should not be considered missing if disabled.
     */
    public function testServerHeartbeatNotMissingWhenDisabled()
    {
        $heartbeat = new TimeHeartbeatMock(0, $this->time);

        $this->time->expects(self::atLeastOnce())
            ->method('__invoke')
            ->willReturnOnConsecutiveCalls(100, 999);

        $heartbeat->serverBeat();

        self::assertFalse($heartbeat->isServerHeartbeatMissing());
    }
}
