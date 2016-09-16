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

        $io = new StreamIO();
        $io->open('tcp', $this->serverHost, $this->serverPort, [
            'connection_timeout' => 0.5,
            'timeout' => 0.1,
        ]);

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

        $io = new StreamIO();
        $io->open($this->serverProtocol, $this->serverHost, $this->serverPort, [
            'connection_timeout' => 0.5,
            'timeout' => 0.1,
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

        $io = new StreamIO();
        $io->open('tcp', $this->serverHost, $this->serverPort, [
            'connection_timeout' => 0.5,
            'timeout' => 0.1,
        ]);

        $io->write('ping');
        $io->close();

        $this->assertServerReceive('ping');
    }

    /**
     * Reading.
     */
    public function testReading()
    {
        $this->serverStart();

        $io = new StreamIO();
        $io->open('tcp', $this->serverHost, $this->serverPort, [
            'connection_timeout' => 0.5,
            'timeout' => 0.1,
        ]);

        $this->serverWrite('pingpo');

        $readOne = $io->read(4, true);
        $readTwo = $io->read(4, true);

        $io->close();

        self::assertEquals('ping', $readOne);
        self::assertEquals('po', $readTwo);
    }

    /**
     * Disconnected while reading.
     */
    public function testDisconnectWhileReading()
    {
        $this->expectException(IOException::class);

        $this->serverStart();

        $io = new StreamIO();
        $io->open('tcp', $this->serverHost, $this->serverPort, [
            'connection_timeout' => 0.5,
            'timeout' => 0.1,
        ]);

        $this->serverWrite('pi');
        $this->serverStop();

        self::assertEquals('pi', $io->read(4, true));
        self::assertEquals('', $io->read(4, true));
    }
}
