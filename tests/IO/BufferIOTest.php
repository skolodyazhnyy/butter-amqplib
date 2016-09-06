<?php

namespace ButterAMQPTest\IO;

use ButterAMQP\IO\BufferIO;
use PHPUnit\Framework\TestCase;

class BufferIOTest extends TestCase
{
    /**
     * @var BufferIO
     */
    private $io;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->io = new BufferIO();
    }

    public function testOpenClose()
    {
        $this->io->open('x', 'y', 1);
        $this->io->close();
    }

    public function testReading()
    {
        $this->io->push('foo');
        $this->io->push('bar');

        self::assertEquals('foo', $this->io->peek(3));
        self::assertEquals('foo', $this->io->read(3));
        self::assertEquals('bar', $this->io->peek(3));
        self::assertEquals('bar', $this->io->read(3));
        self::assertNull($this->io->read(3, true, 1));
    }

    public function testWriting()
    {
        $this->io->write('f');
        $this->io->write('oo');
        $this->io->write('bar');

        self::assertEquals('foobar', $this->io->pop());
    }
}
