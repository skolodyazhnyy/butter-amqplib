<?php

namespace AMQLibTest\Integration\RabbitMQ;

class ChannelTest extends TestCase
{
    public function testOpen()
    {
        $this->connection->open();
        $this->connection->channel(1);

        $this->assertMethodSent(20, 10, 1, 'Method "channel.open" (20, 10) should be sent');
        $this->assertMethodReceived(20, 11, 1, 'Method "channel.open-ok" (20, 11) should be received');
    }

    public function testFlowActive()
    {
        $this->connection->open();
        $this->connection->channel(1)
            ->flow(true);

        $this->assertMethodSent(20, 20, 1, 'Method "channel.flow" (20, 20) should be sent');
        $this->assertMethodReceived(20, 21, 1, 'Method "channel.flow-ok" (20, 21) should be received');
    }

    public function testClose()
    {
        $this->connection->open();
        $this->connection->channel(1)
            ->close();

        $this->assertMethodSent(20, 40, 1, 'Method "channel.close" (20, 40) should be sent');
        $this->assertMethodReceived(20, 41, 1, 'Method "channel.close-ok" (20, 41) should be received');
    }
}
