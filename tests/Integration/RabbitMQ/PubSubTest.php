<?php

namespace ButterAMQPTest\Integration\RabbitMQ;

use ButterAMQP\Delivery;
use ButterAMQP\Exchange;
use ButterAMQP\Message;
use ButterAMQP\Queue;
use PHPUnit_Framework_MockObject_MockObject as Mock;

/**
 * @group slow
 * @group integration
 */
class PubSubTest extends TestCase
{
    const BIGGER_TEST_MESSAGE_COUNT = 200;

    public function testPubSub()
    {
        $channel = $this->connection->open()
            ->channel();

        $exchange = $channel->exchange(uniqid('pubsub-'))
            ->define(Exchange::TYPE_TOPIC, Exchange::FLAG_AUTO_DELETE);

        $fooToken = uniqid('foo-token-');
        $barToken = uniqid('bar-token-');

        $fooQueue = $channel->queue()
            ->define(Queue::FLAG_AUTO_DELETE | Queue::FLAG_EXCLUSIVE)
            ->bind($exchange, 'foo');

        $barQueue = $channel->queue()
            ->define(Queue::FLAG_AUTO_DELETE | Queue::FLAG_EXCLUSIVE)
            ->bind($exchange, 'bar');

        $fooCallback = $this->getCallableMock();
        $fooCallback->expects(self::once())
            ->method('__invoke')
            ->willReturnCallback(function (Delivery $delivery) use ($fooToken) {
                self::assertEquals($fooToken, $delivery->getBody());
                $delivery->ack();
                $delivery->cancel();
            });

        $barCallback = $this->getCallableMock();
        $barCallback->expects(self::once())
            ->method('__invoke')
            ->willReturnCallback(function (Delivery $delivery) use ($barToken) {
                self::assertEquals($barToken, $delivery->getBody());
                $delivery->ack();
                $delivery->cancel();
            });

        $fooConsumer = $channel->consume($fooQueue, $fooCallback);
        $barConsumer = $channel->consume($barQueue, $barCallback);

        $channel->publish(new Message($fooToken), $exchange, 'foo');
        $channel->publish(new Message($barToken), $exchange, 'bar');

        while ($fooConsumer->isActive() && $barConsumer->isActive()) {
            $this->connection->serve(true, 1);
        }

        $exchange->delete();

        $this->connection->close();
    }

    public function testPubSub1000Messages()
    {
        $channel = $this->connection->open()
            ->channel();

        $exchange = $channel->exchange(uniqid('pubsub-'))
            ->define(Exchange::TYPE_TOPIC, Exchange::FLAG_AUTO_DELETE);

        $fooTokens = [];
        $barTokens = [];

        $fooQueue = $channel->queue()
            ->define(Queue::FLAG_AUTO_DELETE | Queue::FLAG_EXCLUSIVE)
            ->bind($exchange, 'foo');

        $barQueue = $channel->queue()
            ->define(Queue::FLAG_AUTO_DELETE | Queue::FLAG_EXCLUSIVE)
            ->bind($exchange, 'bar');

        $fooCallback = $this->getCallableMock();
        $fooCallback->expects(self::exactly(self::BIGGER_TEST_MESSAGE_COUNT))
            ->method('__invoke')
            ->willReturnCallback(function (Delivery $delivery) use (&$fooTokens) {
                if (!isset($fooTokens[$delivery->getBody()])) {
                    throw new \Exception('It seems routing is not working (foo)');
                }

                unset($fooTokens[$delivery->getBody()]);
                $delivery->ack();

                if (empty($fooTokens)) {
                    $delivery->cancel();
                }
            });

        $barCallback = $this->getCallableMock();
        $barCallback->expects(self::exactly(self::BIGGER_TEST_MESSAGE_COUNT))
            ->method('__invoke')
            ->willReturnCallback(function (Delivery $delivery) use (&$barTokens) {
                if (!isset($barTokens[$delivery->getBody()])) {
                    throw new \Exception('It seems routing is not working (bar)');
                }

                unset($barTokens[$delivery->getBody()]);
                $delivery->ack();

                if (empty($barTokens)) {
                    $delivery->cancel();
                }
            });

        $fooConsumer = $channel->consume($fooQueue, $fooCallback);
        $barConsumer = $channel->consume($barQueue, $barCallback);

        for ($i = 0; $i < self::BIGGER_TEST_MESSAGE_COUNT; ++$i) {
            $fooToken = uniqid('foo-token-');
            $barToken = uniqid('bar-token-');

            $fooTokens[$fooToken] = true;
            $barTokens[$barToken] = true;

            $channel->publish(new Message($fooToken), $exchange, 'foo');
            $channel->publish(new Message($barToken), $exchange, 'bar');
        }

        while ($fooConsumer->isActive() && $barConsumer->isActive()) {
            $this->connection->serve(true, 1);
        }

        $exchange->delete();

        $this->connection->close();
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
