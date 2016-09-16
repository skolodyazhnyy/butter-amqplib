<?php

namespace ButterAMQPTest\Debug;

use ButterAMQP\Debug\LoggerDecoratedIO;
use ButterAMQP\IOInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;
use Psr\Log\LoggerInterface;

class LoggerDecoratedIOTest extends TestCase
{
    /**
     * @var IOInterface|Mock
     */
    private $decorated;

    /**
     * @var LoggerInterface|Mock
     */
    private $logger;

    /**
     * @var LoggerDecoratedIO
     */
    private $io;

    protected function setUp()
    {
        $this->decorated = $this->createMock(IOInterface::class);

        $this->logger = $this->createMock(LoggerInterface::class);

        $this->io = new LoggerDecoratedIO($this->decorated, $this->logger);
    }

    public function testOpen()
    {
        $this->decorated->expects(self::once())
            ->method('open')
            ->with('tcp', 'localhost', 5672);

        $this->logger->expects(self::once())
            ->method('debug');

        $this->io->open('tcp', 'localhost', 5672);
    }

    public function testClose()
    {
        $this->decorated->expects(self::once())
            ->method('close');

        $this->logger->expects(self::once())
            ->method('debug');

        $this->io->close();
    }

    public function testRead()
    {
        $this->decorated->expects(self::once())
            ->method('read')
            ->with(10, true);

        $this->logger->expects(self::once())
            ->method('debug');

        $this->io->read(10, true);
    }

    public function testWrite()
    {
        $this->decorated->expects(self::once())
            ->method('write')
            ->with('hello', 4);

        $this->logger->expects(self::once())
            ->method('debug');

        $this->io->write('hello', 4);
    }
}
