<?php

namespace ButterAMQPTest\AMQP091;

use ButterAMQP\AMQP091\Channel;
use ButterAMQP\AMQP091\Connection;
use ButterAMQP\Exception\InvalidChannelNumberException;
use ButterAMQP\AMQP091\Framing\Method\ChannelOpen;
use ButterAMQP\AMQP091\Framing\Method\ChannelOpenOk;
use ButterAMQP\AMQP091\Framing\Method\ConnectionClose;
use ButterAMQP\AMQP091\Framing\Method\ConnectionCloseOk;
use ButterAMQP\AMQP091\Framing\Method\ConnectionOpen;
use ButterAMQP\AMQP091\Framing\Method\ConnectionOpenOk;
use ButterAMQP\AMQP091\Framing\Method\ConnectionTune;
use ButterAMQP\Security\AuthenticatorInterface;
use ButterAMQP\Url;
use ButterAMQP\AMQP091\Wire;
use ButterAMQP\AMQP091\WireInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;

class ConnectionTest extends TestCase
{
    /**
     * @var WireInterface|Mock
     */
    private $wire;

    /**
     * @var AuthenticatorInterface|Mock
     */
    private $authenticator;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->wire = $this->createMock(WireInterface::class);
        $this->authenticator = $this->createMock(AuthenticatorInterface::class);

        $this->connection = new Connection(
            Url::parse('amqp://phpunit/foo'),
            $this->wire,
            $this->authenticator
        );
    }

    /**
     * Connection should connect to the server, wait for connection.tune, then send connection.open and wait for reply.
     */
    public function testOpen()
    {
        $this->wire->expects(self::at(0))
            ->method('open')
            ->with(self::isInstanceOf(Url::class))
            ->willReturnSelf();

        $this->wire->expects(self::at(1))
            ->method('subscribe')
            ->with(0, $this->connection);

        $this->wire->expects(self::at(2))
            ->method('wait')
            ->with(0, ConnectionTune::class);

        $this->wire->expects(self::at(3))
            ->method('send')
            ->with(new ConnectionOpen(0, 'foo', '', false));

        $this->wire->expects(self::at(4))
            ->method('wait')
            ->with(0, ConnectionOpenOk::class)
            ->willReturnSelf();

        $this->connection->open();

        self::assertEquals(Connection::STATUS_READY, $this->connection->getStatus());
    }

    /**
     * Connection should be able to open a channel.
     */
    public function testChannel()
    {
        $this->wire->expects(self::at(0))
            ->method('open')
            ->willReturnSelf();

        $this->wire->expects(self::at(1))
            ->method('subscribe')
            ->with(0, self::isInstanceOf(Connection::class));

        $this->wire->expects(self::at(2))
            ->method('wait')
            ->willReturnSelf();

        $this->wire->expects(self::at(3))
            ->method('send')
            ->willReturnSelf();

        $this->wire->expects(self::at(4))
            ->method('wait')
            ->willReturnSelf();

        $this->wire->expects(self::at(5))
            ->method('subscribe')
            ->with(1, self::isInstanceOf(Channel::class));

        $this->wire->expects(self::at(6))
            ->method('send')
            ->with(new ChannelOpen(1, ''));

        $this->wire->expects(self::at(7))
            ->method('wait')
            ->with(1, ChannelOpenOk::class);

        $channel = $this->connection->channel();

        self::assertInstanceOf(Channel::class, $channel);
    }

    /**
     * Connection should throw an exception if requested channel ID is invalid.
     */
    public function testChannelInvalidId()
    {
        $this->expectException(InvalidChannelNumberException::class);

        $this->connection->channel('-1210');
    }

    /**
     * Connection should send connection.close, wait for reply and disconnect from the server.
     */
    public function testClose()
    {
        $this->wire->expects(self::at(0))
            ->method('send')
            ->with(new ConnectionClose(0, 0, '', 0, 0));

        $this->wire->expects(self::at(1))
            ->method('wait')
            ->with(0, ConnectionCloseOk::class);

        $this->wire->expects(self::at(2))
            ->method('close')
            ->with();

        $this->connection->close();

        self::assertEquals(Connection::STATUS_CLOSED, $this->connection->getStatus());
    }

    /**
     * Connection should fetch next frame when serving.
     */
    public function testServe()
    {
        $this->wire->expects(self::once())
            ->method('next')
            ->with(true);

        $this->connection->serve(true);
    }
}
