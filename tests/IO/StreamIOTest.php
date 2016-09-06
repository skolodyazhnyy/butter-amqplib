<?php

namespace ButterAMQPTest\IO;

use ButterAMQP\Exception\IOException;
use ButterAMQP\IO\StreamIO;

/**
 * @group slow
 */
class StreamIOTest extends TestCase
{
    /**
     * Connect to the server and read/write few bytes.
     */
    public function testConnecting()
    {
        $this->serverStart();

        $io = new StreamIO(1, 1);
        $io->open('tcp', $this->serverHost, $this->serverPort);

        $io->write('ping');
        $this->assertServerReceive('ping');

        $this->serverWrite('pong');
        $data = $io->read(4, true);

        $io->close();

        self::assertEquals('pong', $data);
    }

    public function testSecureConnection()
    {
        $this->serverStart(true);

        $io = new StreamIO(1, 1);
        $io->open($this->serverProtocol, $this->serverHost, $this->serverPort, [
            'certfile' => $this->serverCert,
            'verify' => false,
            'allow_self_signed' => true,
        ]);

        $io->write('ping');
        $this->assertServerReceive('ping');

        $this->serverWrite('pong');
        $data = $io->read(4, true);

        $io->close();

        self::assertEquals('pong', $data);
    }

    /**
     * Writing.
     */
    public function testWriting()
    {
        $this->serverStart();

        $io = new StreamIO(1, 1);
        $io->open('tcp', $this->serverHost, $this->serverPort);
        $io->write('ping');
        $io->close();

        $this->assertServerReceive('ping');
    }

    /**
     * Peeking.
     */
    public function testPeeking()
    {
        $this->serverStart();

        $io = new StreamIO(1, 0.1);
        $io->open('tcp', $this->serverHost, $this->serverPort);

        $this->serverWrite('ping');

        $peekOne = $io->peek(4, true);
        $peekTwo = $io->peek(4, true);
        $peekThree = $io->peek(5, true);

        $io->close();

        self::assertEquals('ping', $peekOne);
        self::assertEquals('ping', $peekTwo);
        self::assertNull($peekThree);
    }

    /**
     * Reading.
     */
    public function testReading()
    {
        $this->serverStart();

        $io = new StreamIO(1, 0.1);
        $io->open('tcp', $this->serverHost, $this->serverPort);

        $this->serverWrite('pingpo');

        $readOne = $io->read(4, true);
        $readTwo = $io->read(4, true);

        $io->close();

        self::assertEquals('ping', $readOne);
        self::assertNull($readTwo);
    }

    /**
     * Disconnected while reading.
     */
    public function testDisconnectWhileReading()
    {
        $this->expectException(IOException::class);

        $this->serverStart();

        $io = new StreamIO(1, 0.1);
        $io->open('tcp', $this->serverHost, $this->serverPort);

        $this->serverWrite('pi');
        $this->serverStop();

        self::assertNull($io->read(4, true));
        self::assertNull($io->read(4, true));
    }

    /**
     * Disconnected while writing.
     */
    public function testDisconnectWhileWriting()
    {
        $this->expectException(IOException::class);

        $this->serverStart();

        $io = new StreamIO();
        $io->open('tcp', $this->serverHost, $this->serverPort);

        $io->write('foo');

        $this->serverForceStop();

        $io->write('bar');
    }
}
