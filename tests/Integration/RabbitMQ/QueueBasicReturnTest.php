<?php

namespace ButterAMQPTest\Integration\RabbitMQ;

use ButterAMQP\Exception\NoReturnException;
use ButterAMQP\Message;
use ButterAMQP\Returned;
use PHPUnit_Framework_MockObject_MockObject as Mock;

/**
 * @group slow
 * @group integration
 */
class QueueBasicReturnTest extends TestCase
{
    public function testReturnMandatory()
    {
        $token = uniqid('token-');

        $onReturnCallable = $this->getCallableMock();
        $onReturnCallable->expects(self::once())
            ->method('__invoke')
            ->willReturn(function (Returned $message) use ($token) {
                self::assertEquals($token, $message->getBody());
            });

        $channel = $this->connection->open()
            ->channel();

        $channel->onReturn($onReturnCallable);

        $channel->publish(new Message($token), '', $token, Message::FLAG_MANDATORY);

        $this->connection->serve(true);
    }

    public function testReturnWithoutCallback()
    {
        $this->expectException(NoReturnException::class);

        $channel = $this->connection->open()
            ->channel();

        $channel->publish(new Message(''), '', '', Message::FLAG_MANDATORY);

        $this->connection->serve(true);
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
