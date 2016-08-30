<?php

namespace AMQPLibTest\Integration\RabbitMQ;

class ConnectionTest extends TestCase
{
    public function testConnect()
    {
        $this->connection->open();

        $this->assertMethodReceived(10, 10, 0, 'Method "connection.start" (10, 10) should be received');
        $this->assertMethodSent(10, 11, 0, 'Method "connection.start-ok" (10, 11) should be sent');

        $this->assertMethodReceived(10, 30, 0, 'Method "connection.tune" (10, 30) should be received');
        $this->assertMethodSent(10, 31, 0, 'Method "connection.tune-ok" (10, 31) should be sent');

        $this->assertMethodSent(10, 40, 0, 'Method "connection.open" (10, 40) should be sent');
        $this->assertMethodReceived(10, 41, 0, 'Method "connection.open-ok" (10, 41) should be received');
    }

    public function testClose()
    {
        $this->connection->open();
        $this->connection->close();

        $this->assertMethodSent(10, 50, 0, 'Method "connection.close" (10, 50) should be sent');
        $this->assertMethodReceived(10, 51, 0, 'Method "connection.start-ok" (10, 51) should be received');
    }
}
