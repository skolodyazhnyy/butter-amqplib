<?php

namespace AMQLibTest;

use AMQLib\ChannelInterface;
use AMQLib\Consumer;
use AMQLib\Delivery;
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
     * Consumer should call channel to cancel consuming.
     */
    public function testCancel()
    {
        $this->channel->expects(self::once())
            ->method('cancel')
            ->with('foo.consumer');

        $this->delivery->cancel();
    }

    /**
     * Consumer should provide information about delivery.
     */
    public function testGettingProps()
    {
        self::assertEquals('foo.consumer', $this->delivery->getConsumerTag());
        self::assertEquals(11, $this->delivery->getDeliveryTag());
        self::assertEquals(false, $this->delivery->isRedeliver());
        self::assertEquals('bar.exchange', $this->delivery->getExchange());
        self::assertEquals('baz.routing', $this->delivery->getRoutingKey());
    }
}
