<?php

namespace AMQLibTest;

use AMQLib\ChannelInterface;
use AMQLib\Consumer;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;

class ConsumerTest extends TestCase
{
    /**
     * @var ChannelInterface|Mock
     */
    private $channel;

    /**
     * @var Consumer
     */
    private $consumer;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->channel = $this->createMock(ChannelInterface::class);
        $this->consumer = new Consumer($this->channel, 'foo');
    }

    /**
     * Consumer should call channel to cancel consuming.
     */
    public function testCancel()
    {
        $this->channel->expects(self::once())
            ->method('cancel')
            ->with('foo');

        $this->consumer->cancel();
    }

    /**
     * Consumer should cast to string as tag name.
     */
    public function testToString()
    {
        self::assertEquals('foo', $this->consumer);
    }

    /**
     * Consumer should know its tag.
     */
    public function testTag()
    {
        self::assertEquals('foo', $this->consumer->tag());
    }
}
