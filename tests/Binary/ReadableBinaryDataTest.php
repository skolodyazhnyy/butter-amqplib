<?php

namespace ButterAMQPTest\Binary;

use ButterAMQP\Debug\ReadableBinaryData;
use PHPUnit\Framework\TestCase;

class ReadableBinaryDataTest extends TestCase
{
    public function testReadableDataRendering()
    {
        self::assertEquals(
            'data 11 bytes: 0 102[f] 111[o] 111[o] 79[O] 98[b] 97[a] 114[r] 241 17 1',
            (string) new ReadableBinaryData('data', "\x00foo\x4Fbar\xF1\x11\x01")
        );
    }
}
