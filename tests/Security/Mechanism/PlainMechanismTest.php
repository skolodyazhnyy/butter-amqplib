<?php

namespace ButterAMQPTest\Security\Mechanism;

use ButterAMQP\Security\Mechanism\PlainMechanism;

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
            "\x00guest\x00batman1",
            $this->mechanism->getResponse('guest', 'batman1')
        );
    }
}
