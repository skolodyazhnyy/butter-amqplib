<?php

namespace ButterAMQPTest\IO;

use ButterAMQP\IO\SocketIO;

class StreamSocketIO extends TestCase
{
    /**
     * Connect to the server and read/write few bytes.
     */
    public function testConnecting()
    {
        $io = new SocketIO();
        $io->open($this->host, $this->port);
        $io->write('ping');

        $this->assertServerReceive('ping');
        $this->serverWrite('pong');

        $data = $io->read(4, true, 1);

        $io->close();

        self::assertEquals('pong', $data);
    }
}
