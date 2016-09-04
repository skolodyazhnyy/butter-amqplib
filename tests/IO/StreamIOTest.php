<?php

namespace ButterAMQPTest\IO;

use ButterAMQP\IO\StreamIO;

class StreamIOTest extends TestCase
{
    /**
     * Connect to the server and read/write few bytes.
     */
    public function testConnecting()
    {
        $io = new StreamIO();
        $io->open($this->serverHost, $this->serverPort);

        $io->write('ping');
        $this->assertServerReceive('ping');

        $this->serverWrite('pong');
        $data = $io->read(4, true, 1);

        $io->close();

        self::assertEquals('pong', $data);
    }

    /**
     * Writing.
     */
    public function testWriting()
    {
        $io = new StreamIO();
        $io->open($this->serverHost, $this->serverPort);
        $io->write('ping');
        $io->close();

        $this->assertServerReceive('ping');
    }

    /**
     * Peeking.
     */
    public function testPeeking()
    {
        $io = new StreamIO();
        $io->open($this->serverHost, $this->serverPort);

        $this->serverWrite('ping');

        $peekOne = $io->peek(4, true, 1);
        $peekTwo = $io->peek(4, true, 1);
        $peekThree = $io->peek(5, true, 0.1);

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
        $io = new StreamIO();
        $io->open($this->serverHost, $this->serverPort);

        $this->serverWrite('pingpo');

        $readOne = $io->read(4, true, 1);
        $readTwo = $io->read(4, true, 1);

        $io->close();

        self::assertEquals('ping', $readOne);
        self::assertNull($readTwo);
    }
}
