<?php

namespace AMQPLibTest\Authenticator\Mechanism;

use AMQPLib\Security\Mechanism\PlainMechanism;

class PlainMechanismTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PlainMechanism
     */
    private $mechanism;

    protected function setUp()
    {
        $this->mechanism = new PlainMechanism();
    }

    public function testName()
    {
        $this->assertEquals('PLAIN', $this->mechanism->getName());
    }

    public function testResponse()
    {
        $this->assertEquals(
            "\x00guest\x00guest",
            $this->mechanism->getResponse('guest', 'guest')
        );
    }
}
