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

        $this->heartbeat = new TimeHeartbeat(10, $this->time);
    }

    public function testShouldRequireHeartbeat()
    {
        $this->time->expects(self::atLeastOnce())
            ->method('__invoke')
            ->willReturnOnConsecutiveCalls(100, 110);

        $this->heartbeat->clientBeat();

        self::assertTrue($this->heartbeat->shouldSendHeartbeat());
    }

    public function testShouldNotRequireHeartbeatTooOften()
    {
        $this->time->expects(self::atLeastOnce())
            ->method('__invoke')
            ->willReturnOnConsecutiveCalls(100, 109);

        $this->heartbeat->clientBeat();
        $this->heartbeat->setClientBeatFactor(1);

        self::assertFalse($this->heartbeat->shouldSendHeartbeat());
    }

    public function testShouldNotSendHeartbeatWhenDisabled()
    {
        $heartbeat = new TimeHeartbeat(0, $this->time);

        $this->time->expects(self::atLeastOnce())
            ->method('__invoke')
            ->willReturnOnConsecutiveCalls(100, 999);

        $heartbeat->clientBeat();
        $this->heartbeat->setClientBeatFactor(1);

        self::assertFalse($heartbeat->shouldSendHeartbeat());
    }

    public function testServerHeartbeatMissing()
    {
        $this->time->expects(self::atLeastOnce())
            ->method('__invoke')
            ->willReturnOnConsecutiveCalls(100, 120);

        $this->heartbeat->serverBeat();

        self::assertTrue($this->heartbeat->isServerHeartbeatMissing());
    }

    public function testServerHeartbeatNotMissing()
    {
        $this->time->expects(self::atLeastOnce())
            ->method('__invoke')
            ->willReturnOnConsecutiveCalls(100, 110);

        $this->heartbeat->serverBeat();

        self::assertFalse($this->heartbeat->isServerHeartbeatMissing());
    }

    public function testServerHeartbeatNotMissingWhenDisabled()
    {
        $heartbeat = new TimeHeartbeat(0, $this->time);

        $this->time->expects(self::atLeastOnce())
            ->method('__invoke')
            ->willReturnOnConsecutiveCalls(100, 999);

        $heartbeat->serverBeat();

        self::assertFalse($heartbeat->isServerHeartbeatMissing());
    }
}
