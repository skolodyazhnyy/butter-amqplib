<?php

namespace ButterAMQPTest\Integration\RabbitMQ;

use ButterAMQP\Confirm;
use ButterAMQP\Message;
use ButterAMQP\Queue;

/**
 * @group slow
 * @group integration
 */
class ConfirmModeTest extends TestCase
{
    public function testConfirm()
    {
        $unconfirmed = [];

        $channel = $this->connection->open()
            ->channel();

        $channel->selectConfirm(function (Confirm $confirm) use (&$unconfirmed) {
            $min = empty($unconfirmed) ? 1 : min(array_keys($unconfirmed));

            $numbers = $confirm->isMultiple() ? range($min, $confirm->getDeliveryTag()) :
                [$confirm->getDeliveryTag()];

            foreach ($numbers as $number) {
                unset($unconfirmed[$number]);
            }
        });

        $queue = $channel->queue()
            ->define(Queue::FLAG_AUTO_DELETE);

        $channel->publish(new Message(''), '', $queue);
        $unconfirmed[1] = true;

        $channel->publish(new Message(''), '', $queue);
        $unconfirmed[2] = true;

        $channel->publish(new Message(''), '', $queue);
        $unconfirmed[3] = true;

        $timeout = $this->createTimeout(1);

        while (!empty($unconfirmed) && !$timeout()) {
            $this->connection->serve(true);
        }

        self::assertEquals([], $unconfirmed);
    }

    /**
     * Create a timeout closure.
     *
     * @param int $delay
     *
     * @return \Closure
     */
    private function createTimeout($delay)
    {
        $end = time() + $delay;

        return function () use ($end) {
            return time() >= $end;
        };
    }
}
