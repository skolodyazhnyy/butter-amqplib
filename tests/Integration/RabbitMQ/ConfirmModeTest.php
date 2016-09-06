<?php

namespace ButterAMQPTest\Integration\RabbitMQ;

use ButterAMQP\Confirm;
use ButterAMQP\Message;
use ButterAMQP\Queue;
use PHPUnit_Framework_MockObject_MockObject as Mock;

/**
 * @group slow
 * @group integration
 */
class ConfirmModeTest extends TestCase
{
    public function testConfirm()
    {
        $unconfirmed = [1, 2, 3];

        $onConfirmCallable = $this->getCallableMock();
        $onConfirmCallable->expects(self::atLeastOnce())
            ->method('__invoke')
            ->willReturnCallback(function (Confirm $c) use (&$unconfirmed) {
                foreach ($unconfirmed as $key => $number) {
                    if ($number == $c->getDeliveryTag() || ($c->isMultiple() && $number < $c->getDeliveryTag())) {
                        unset($unconfirmed[$key]);
                    }
                }

                $unconfirmed = array_values($unconfirmed);
            });

        $channel = $this->connection->open()
            ->channel();

        $channel->onConfirm($onConfirmCallable);

        $queue = $channel->queue()
            ->define(Queue::FLAG_AUTO_DELETE);

        $channel->publish(new Message(''), '', $queue);
        $channel->publish(new Message(''), '', $queue);
        $channel->publish(new Message(''), '', $queue);

        $this->connection->serve(true);
        $this->connection->serve(true);
        $this->connection->serve(true);

        self::assertEquals([], $unconfirmed);
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
