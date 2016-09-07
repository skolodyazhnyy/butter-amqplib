<?php

namespace ButterAMQPTest\Security\Mechanism;

use ButterAMQP\Security\Mechanism\AMQPlainMechanism;

class AMQPlainMechanismTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var AMQPlainMechanism
     */
    private $mechanism;

    protected function setUp()
    {
        $this->mechanism = new AMQPlainMechanism();
    }

    public function testName()
    {
        $this->assertEquals('AMQPLAIN', $this->mechanism->getName());
    }

    public function testResponse()
    {
        $this->assertEquals(
            "\x05LOGINS\x00\x00\x00\x05guest\x08PASSWORDS\x00\x00\x00\x07batman1",
            $this->mechanism->getResponse('guest', 'batman1')
        );
    }
}
