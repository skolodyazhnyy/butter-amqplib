<?php

namespace ButterAMQPTest\IO;

use ButterAMQP\IO\NullIO;
use PHPUnit\Framework\TestCase;

class NullIOTest extends TestCase
{
    /**
     * @var NullIO
     */
    private $io;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->io = new NullIO();
    }

    public function testNothingHappens()
    {
        self::assertSame($this->io, $this->io->open('x', 'y', 1));
        self::assertSame($this->io, $this->io->close());
        self::assertSame($this->io, $this->io->write(''));
        self::assertNull($this->io->read(1));
        self::assertNull($this->io->peek(1));
    }
}
