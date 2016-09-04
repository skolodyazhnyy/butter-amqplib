<?php

namespace ButterAMQPTest;

use ButterAMQP\Channel;
use ButterAMQP\Connection;
use ButterAMQP\Exception\ConnectionException;
use ButterAMQP\Exception\InvalidChannelNumberException;
use ButterAMQP\Framing\Method\ChannelOpen;
use ButterAMQP\Framing\Method\ChannelOpenOk;
use ButterAMQP\Framing\Method\ConnectionBlocked;
use ButterAMQP\Framing\Method\ConnectionClose;
use ButterAMQP\Framing\Method\ConnectionCloseOk;
use ButterAMQP\Framing\Method\ConnectionOpen;
use ButterAMQP\Framing\Method\ConnectionOpenOk;
use ButterAMQP\Framing\Method\ConnectionStart;
use ButterAMQP\Framing\Method\ConnectionStartOk;
use ButterAMQP\Framing\Method\ConnectionTune;
use ButterAMQP\Framing\Method\ConnectionTuneOk;
use ButterAMQP\Framing\Method\ConnectionUnblocked;
use ButterAMQP\Heartbeat\TimeHeartbeat;
use ButterAMQP\Security\AuthenticatorInterface;
use ButterAMQP\Security\MechanismInterface;
use ButterAMQP\WireInterface;
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
        $this->connection = new Connection('amqp://phpunit/foo', $this->wire, $this->authenticator);
    }

    /**
     * Connection should connect to the server, wait for connection.tune, then send connection.open and wait for reply.
     */
    public function testOpen()
    {
        $this->wire->expects(self::at(0))
            ->method('open')
            ->with('phpunit', 5672)
            ->willReturnSelf();

        $this->wire->expects(self::at(1))
            ->method('subscribe')
            ->with(0, $this->connection);

        $this->wire->expects(self::at(2))
            ->method('wait')
            ->with(0, ConnectionTune::class);

        $this->wire->expects(self::at(3))
            ->method('send')
            ->with(0, new ConnectionOpen('foo', '', false));

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
            ->method('subscribe')
            ->with(1, self::isInstanceOf(Channel::class));

        $this->wire->expects(self::at(1))
            ->method('send')
            ->with(1, new ChannelOpen(''));

        $this->wire->expects(self::at(2))
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
            ->with(0, new ConnectionClose(0, '', 0, 0));

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
            ->with(true, 10);

        $this->connection->serve(true, 10);
    }

    /**
     * Connection should reply connection.start-ok when connection.start is received.
     */
    public function testDispatchConnectionStart()
    {
        $mechanism = $this->createMock(MechanismInterface::class);
        $mechanism->expects(self::once())
            ->method('getName')
            ->willReturn('PLAIN');

        $mechanism->expects(self::once())
            ->method('getResponse')
            ->willReturn('**response**');

        $this->authenticator->expects(self::once())
            ->method('get')
            ->with(['AMQPLAIN', 'PLAIN'])
            ->willReturn($mechanism);

        $this->wire->expects(self::once())
            ->method('send')
            ->with(0, self::isInstanceOf(ConnectionStartOk::class))
            ->willReturnCallback(function ($channel, ConnectionStartOk $frame) {
                self::assertEquals('es-ES', $frame->getLocale());
                self::assertEquals('PLAIN', $frame->getMechanism());
                self::assertEquals('**response**', $frame->getResponse());

                return $this->wire;
            });

        $this->connection->dispatch(new ConnectionStart(0, 9, [], 'AMQPLAIN PLAIN', 'es-ES en-US'));
    }

    /**
     * Connection should reply connection.tune-ok when connection.tune is received.
     */
    public function testDispatchConnectionTune()
    {
        $this->wire->expects(self::once())
            ->method('setHeartbeat')
            ->with(new TimeHeartbeat(3))
            ->willReturnSelf();

        $this->wire->expects(self::once())
            ->method('setFrameMax')
            ->with(2)
            ->willReturnSelf();

        $this->wire->expects(self::once())
            ->method('send')
            ->with(0, new ConnectionTuneOk(1, 2, 3));

        $this->connection->dispatch(new ConnectionTune(1, 2, 3));
    }

    /**
     * Connection should negotiate tuning parameters and choose lower, but non-zero values.
     */
    public function testDispatchConnectionTuneNegotiate()
    {
        $this->wire->expects(self::once())
            ->method('setHeartbeat')
            ->willReturnSelf();

        $this->wire->expects(self::once())
            ->method('setFrameMax')
            ->willReturnSelf();

        $this->wire->expects(self::once())
            ->method('send')
            ->with(0, new ConnectionTuneOk(1, 2, 1));

        $this->connection->setChannelMax(3);
        $this->connection->setFrameMax(0);
        $this->connection->setHeartbeat(1);

        $this->connection->dispatch(new ConnectionTune(1, 2, 3));
    }

    /**
     * Connection should reply connection.close-ok and close connection.
     */
    public function testDispatchConnectionClose()
    {
        $this->wire->expects(self::once())
            ->method('send')
            ->with(0, new ConnectionCloseOk());

        $this->wire->expects(self::once())
            ->method('close');

        $this->connection->dispatch(new ConnectionClose(0, '', 0, 0));
    }

    /**
     * Connection should reply connection.tune-ok when connection.tune is received.
     */
    public function testDispatchConnectionCloseWithException()
    {
        $this->expectException(ConnectionException::class);

        $this->wire->expects(self::once())
            ->method('send')
            ->with(0, new ConnectionCloseOk());

        $this->wire->expects(self::once())
            ->method('close');

        $this->connection->dispatch(new ConnectionClose(320, 'Failed', 0, 0));
    }

    /**
     * Connection should set status to BLOCKED when connection.blocked is received.
     */
    public function testDispatchConnectionBlocked()
    {
        $this->connection->dispatch(new ConnectionBlocked('no-reason'));

        self::assertEquals(Connection::STATUS_BLOCKED, $this->connection->getStatus());
    }

    /**
     * Connection should set status to UNBLOCKED when connection.unblocked is received.
     */
    public function testDispatchConnectionUnblocked()
    {
        $this->connection->dispatch(new ConnectionUnblocked());

        self::assertEquals(Connection::STATUS_READY, $this->connection->getStatus());
    }
}
