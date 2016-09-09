<?php

namespace ButterAMQPTest\AMQP091;

use ButterAMQP\Exception\InvalidFrameEndingException;
use ButterAMQP\AMQP091\Framing\Content;
use ButterAMQP\AMQP091\Framing\Heartbeat;
use ButterAMQP\AMQP091\Framing\Method\ConnectionBlocked;
use ButterAMQP\HeartbeatInterface;
use ButterAMQP\IO\BufferIO;
use ButterAMQP\IOInterface;
use ButterAMQP\Url;
use ButterAMQP\AMQP091\Wire;
use ButterAMQP\WireSubscriberInterface;
use PHPUnit\Framework\TestCase;

class WireTest extends TestCase
{
    /**
     * @var BufferIO
     */
    private $io;

    /**
     * @var Wire
     */
    private $wire;

    protected function setUp()
    {
        $this->io = new BufferIO();
        $this->wire = new Wire($this->io);
    }

    /**
     * Wire should open IO connection and send protocol header.
     */
    public function testConnect()
    {
        $io = $this->createMock(IOInterface::class);
        $wire = new Wire($io);

        $io->expects(self::once())
            ->method('open')
            ->with('ssl', 'amqp.server', 5621)
            ->willReturnSelf();

        $io->expects(self::once())
            ->method('write')
            ->with("AMQP\x00\x00\x09\x01");

        $wire->open(Url::parse('amqps://amqp.server:5621'));
    }

    /**
     * Wire should close IO connection.
     */
    public function testClose()
    {
        $io = $this->createMock(IOInterface::class);
        $wire = new Wire($io);

        $io->expects(self::once())
            ->method('close');

        $wire->close();
    }

    /**
     * Wire should be able to read and decode frame from the IO.
     */
    public function testNextFrame()
    {
        $this->io->push("\x08\x00\x00\x00\x00\x00\x00\xCE");

        $frame = $this->wire->next();

        self::assertInstanceOf(Heartbeat::class, $frame);
    }

    /**
     * Wire should return null if there is no data pending to be read.
     */
    public function testNextFrameEmptyBuffer()
    {
        $frame = $this->wire->next(true);

        self::assertNull($frame);
    }

    /**
     * Wire should return null if there is not enough data.
     */
    public function testNextFrameIncomplete()
    {
        $this->io->push("\x03\x00\x00\x00\x00\x00\x01\x02");

        $frame = $this->wire->next(true);

        self::assertNull($frame);
    }

    /**
     * Wire should return null if header of the frame can be read, but payload is missing.
     */
    public function testNextFrameHeader()
    {
        $this->io->push("\x08\x00\x00\x00\x00\x00\x00");

        $frame = $this->wire->next(true);

        self::assertNull($frame);
    }

    /**
     * Wire should return null if header of the frame can be read, but payload is missing.
     */
    public function testNextFrameInvalidEnding()
    {
        $this->expectException(InvalidFrameEndingException::class);

        $this->io->push("\x08\x00\x00\x00\x00\x00\x00\xAA");

        $this->wire->next(true);
    }

    /**
     * Wire should notify channel subscriber about new frame.
     */
    public function testSubscribingToChannel()
    {
        $chZeroSubscriber = $this->createMock(WireSubscriberInterface::class);
        $chZeroSubscriber->expects(self::never())
            ->method('dispatch');

        $chOneSubscriber = $this->createMock(WireSubscriberInterface::class);
        $chOneSubscriber->expects(self::once())
            ->method('dispatch')
            ->with(self::isInstanceOf(Heartbeat::class));

        $this->io->push("\x08\x00\x01\x00\x00\x00\x00\xCE");

        $this->wire->subscribe(0, $chZeroSubscriber);
        $this->wire->subscribe(1, $chOneSubscriber);
        $this->wire->next(true);
    }

    /**
     * Wire should register server heartbeat.
     */
    public function testNextRegisterServerHeartbeat()
    {
        $heartbeat = $this->createMock(HeartbeatInterface::class);
        $heartbeat->expects(self::once())
            ->method('serverBeat');

        $this->wire->setHeartbeat($heartbeat);

        $this->io->push("\x03\x00\x01\x00\x00\x00\x00\xCE");

        $this->wire->next(true);
    }

    /**
     * Wire should send heartbeat.
     */
    public function testNextSendHeartbeat()
    {
        $heartbeat = $this->createMock(HeartbeatInterface::class);
        $heartbeat->expects(self::once())
            ->method('shouldSendHeartbeat')
            ->willReturn(true);

        $this->wire->setHeartbeat($heartbeat);

        $this->wire->next(true);

        self::assertEquals("\x08\x00\x00\x00\x00\x00\x00\xCE", $this->io->pop(8));
    }

    /**
     * Wire should send frames.
     */
    public function testSend()
    {
        $this->wire->send(new Content(1, "\xBA\xAB"));

        self::assertEquals("\x03\x00\x01\x00\x00\x00\x02\xBA\xAB\xCE", $this->io->pop(10));
    }

    /**
     * Wire should send cut content frames into pieces if they are too big.
     */
    public function testSendCutContentIntoPieces()
    {
        $this->wire->setFrameMax(16);

        $this->wire->send(new Content(1, str_repeat("\x00\x01", 8).str_repeat("\xFE\xFF", 7)));

        /*
         * Max frame size is 16 bytes, 8 used by header in each frame, so 30 bytes of content should be
         * cut into 4 pieces of 8, 8, 8 and 6 bytes.
         */
        self::assertEquals("\x03\x00\x01\x00\x00\x00\x08".str_repeat("\x00\x01", 4)."\xCE", $this->io->pop(16));
        self::assertEquals("\x03\x00\x01\x00\x00\x00\x08".str_repeat("\x00\x01", 4)."\xCE", $this->io->pop(16));
        self::assertEquals("\x03\x00\x01\x00\x00\x00\x08".str_repeat("\xFE\xFF", 4)."\xCE", $this->io->pop(16));
        self::assertEquals("\x03\x00\x01\x00\x00\x00\x06".str_repeat("\xFE\xFF", 3)."\xCE", $this->io->pop(16));
    }

    /**
     * Wire should register client heartbeat.
     */
    public function testSendShouldRegisterClientHeartbeat()
    {
        $heartbeat = $this->createMock(HeartbeatInterface::class);
        $heartbeat->expects(self::once())
            ->method('clientBeat');

        $this->wire->setHeartbeat($heartbeat);

        $this->wire->send(new Content(1, "\xBA\xAB"));
    }

    /**
     * Wire should wait for frame to appear in the reading buffer.
     */
    public function testWait()
    {
        // Wrong frame type should be skipped
        $this->io->push("\x08\x00\x00\x00\x00\x00\x00\xCE");

        // Wrong channel number should be skipped
        $this->io->push("\x03\x00\x01\x00\x00\x00\x01\x01\xCE");

        // All good, should be returned
        $this->io->push("\x03\x00\x02\x00\x00\x00\x01\x02\xCE");

        $content = $this->wire->wait(2, Content::class);

        self::assertEquals("\x02", $content->getData());
    }

    /**
     * Wire should wait for one of the given frame frames to appear in the reading buffer.
     */
    public function testWaitForTwo()
    {
        // Wrong frame type should be skipped
        $this->io->push("\x08\x00\x00\x00\x00\x00\x00\xCE");

        // Wrong channel number should be skipped
        $this->io->push("\x03\x00\x01\x00\x00\x00\x01\x01\xCE");

        // All good, should be returned
        $this->io->push("\x03\x00\x02\x00\x00\x00\x01\x02\xCE");

        $content = $this->wire->wait(2, [Content::class, ConnectionBlocked::class]);

        self::assertEquals("\x02", $content->getData());
    }
}
