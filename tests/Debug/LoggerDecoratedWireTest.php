<?php

namespace ButterAMQPTest\Debug;

use ButterAMQP\AMQP091\Framing\Content;
use ButterAMQP\AMQP091\WireInterface;
use ButterAMQP\AMQP091\WireSubscriberInterface;
use ButterAMQP\Debug\LoggerDecoratedWire;
use ButterAMQP\Heartbeat\NullHeartbeat;
use ButterAMQP\Url;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use Psr\Log\LoggerInterface;

class LoggerDecoratedWireTest extends TestCase
{
    /**
     * @var WireInterface|Mock
     */
    private $decorated;

    /**
     * @var LoggerInterface|Mock
     */
    private $logger;

    /**
     * @var LoggerDecoratedWire
     */
    private $wire;

    protected function setUp()
    {
        $this->decorated = $this->createMock(WireInterface::class);

        $this->logger = $this->createMock(LoggerInterface::class);

        $this->wire = new LoggerDecoratedWire($this->decorated, $this->logger);
    }

    public function testOpen()
    {
        $url = new Url();

        $this->decorated->expects(self::once())
            ->method('open')
            ->with($url);

        $this->wire->open($url);
    }

    public function testClose()
    {
        $this->decorated->expects(self::once())
            ->method('close');

        $this->wire->close();
    }

    public function testWait()
    {
        $this->decorated->expects(self::once())
            ->method('wait')
            ->with(10, ['foo', 'bar']);

        $this->logger->expects(self::once())
            ->method('info');

        $this->wire->wait(10, ['foo', 'bar']);
    }

    public function testNext()
    {
        $this->decorated->expects(self::once())
            ->method('next')
            ->with(true);

        $this->wire->next(true);
    }

    public function testSend()
    {
        $frame = new Content(1, '');

        $this->decorated->expects(self::once())
            ->method('send')
            ->with($frame);

        $this->logger->expects(self::once())
            ->method('info');

        $this->wire->send($frame);
    }

    public function testSubscribe()
    {
        $subscriber = $this->createMock(WireSubscriberInterface::class);

        $this->decorated->expects(self::once())
            ->method('subscribe')
            ->with(11, $subscriber);

        $this->wire->subscribe(11, $subscriber);
    }

    public function testSetHeartbeat()
    {
        $heartbeat = new NullHeartbeat();

        $this->decorated->expects(self::once())
            ->method('setHeartbeat')
            ->with($heartbeat);

        $this->wire->setHeartbeat($heartbeat);
    }

    public function testSetFrameMax()
    {
        $this->decorated->expects(self::once())
            ->method('setFrameMax')
            ->with(41);

        $this->wire->setFrameMax(41);
    }
}
