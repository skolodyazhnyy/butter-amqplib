<?php

namespace ButterAMQPTest;

use ButterAMQP\ChannelInterface;
use ButterAMQP\AMQP091\Consumer;
use ButterAMQP\Delivery;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;

class DeliveryTest extends TestCase
{
    /**
     * @var ChannelInterface|Mock
     */
    private $channel;

    /**
     * @var Delivery
     */
    private $delivery;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->channel = $this->createMock(ChannelInterface::class);

        $this->delivery = new Delivery(
            $this->channel,
            'foo.consumer',
            11,
            false,
            'bar.exchange',
            'baz.routing',
            'qux.load',
            []
        );
    }

    /**
     * Delivery should call channel to acknowledge delivery.
     */
    public function testAck()
    {
        $this->channel->expects(self::once())
            ->method('ack')
            ->with(11, true);

        $this->delivery->ack(true);
    }

    /**
     * Delivery should call channel to reject delivery.
     */
    public function testReject()
    {
        $this->channel->expects(self::once())
            ->method('reject')
            ->with(11, true);

        $this->delivery->reject(true);
    }

    /**
     * Delivery should call channel to cancel consuming.
     */
    public function testCancel()
    {
        $this->channel->expects(self::once())
            ->method('cancel')
            ->with('foo.consumer');

        $this->delivery->cancel();
    }

    /**
     * Delivery should throw an exception when cancelling without consumer tag.
     */
    public function testCancelWithoutTag()
    {
        $this->expectException(\LogicException::class);

        $delivery = new Delivery($this->channel, '', 0, false, '', '', '', []);
        $delivery->cancel();
    }

    /**
     * Delivery should provide information about delivery.
     */
    public function testGettingProps()
    {
        self::assertEquals('foo.consumer', $this->delivery->getConsumerTag());
        self::assertEquals(11, $this->delivery->getDeliveryTag());
        self::assertEquals(false, $this->delivery->isRedeliver());
        self::assertEquals('bar.exchange', $this->delivery->getExchange());
        self::assertEquals('baz.routing', $this->delivery->getRoutingKey());
    }

    public function testDebugging()
    {
        self::assertEquals(
            [
                'body' => 'qux.load',
                'properties' => [],
                'consumer_tag' => 'foo.consumer',
                'delivery_tag' => 11,
                'redeliver' => false,
                'exchange' => 'bar.exchange',
                'routing_key' => 'baz.routing',
                'channel_object_hash' => spl_object_hash($this->channel),
            ],
            $this->delivery->__debugInfo()
        );
    }
}
