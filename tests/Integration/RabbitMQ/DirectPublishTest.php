<?php

namespace ButterAMQPTest\Integration\RabbitMQ;

use ButterAMQP\Delivery;
use ButterAMQP\Message;
use ButterAMQP\Queue;
use PHPUnit_Framework_MockObject_MockObject as Mock;

/**
 * @group slow
 * @group integration
 */
class DirectPublishTest extends TestCase
{
    public function testDirectPublish()
    {
        $token = uniqid('token-');

        $channel = $this->connection->open()
            ->channel();

        $queue = $channel->queue()
            ->define(Queue::FLAG_AUTO_DELETE | Queue::FLAG_EXCLUSIVE);

        $callback = $this->getCallableMock();
        $callback->expects(self::once())
            ->method('__invoke')
            ->willReturnCallback(function (Delivery $delivery) use ($token) {
                self::assertEquals($token, $delivery->getBody());
                $delivery->ack();
                $delivery->cancel();
            });

        $consumer = $channel->consume($queue, $callback);

        $channel->publish(new Message($token), '', $queue);

        while ($consumer->isActive()) {
            $this->connection->serve(true, 1);
        }

        $this->connection->close();
    }

    public function testDirectPublish10MbMessage()
    {
        $token = uniqid('token-').str_repeat('1234567890', 1048576);

        $channel = $this->connection->open()
            ->channel();

        $queue = $channel->queue()
            ->define(Queue::FLAG_AUTO_DELETE | Queue::FLAG_EXCLUSIVE);

        $callback = $this->getCallableMock();
        $callback->expects(self::once())
            ->method('__invoke')
            ->willReturnCallback(function (Delivery $delivery) use ($token) {
                self::assertEquals($token, $delivery->getBody());
                $delivery->ack();
                $delivery->cancel();
            });

        $consumer = $channel->consume($queue, $callback);

        $channel->publish(new Message($token), '', $queue);

        while ($consumer->isActive()) {
            $this->connection->serve(true, 1);
        }
    }

    /**
     * @return Mock|callable
     */
    private function getCallableMock()
    {
        return $this->getMockBuilder(\stdClass::class)
            ->setMethods(['__invoke'])
            ->getMock();
    }
}
