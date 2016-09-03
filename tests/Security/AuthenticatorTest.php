<?php

namespace ButterAMQPTest\Security;

use ButterAMQP\Exception\UnsupportedSecurityMechanismException;
use ButterAMQP\Security\Authenticator;
use ButterAMQP\Security\MechanismInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as Mock;

class AuthenticatorTest extends TestCase
{
    /**
     * @var MechanismInterface|Mock
     */
    private $barMechanism;

    /**
     * @var MechanismInterface|Mock
     */
    private $fooMechanism;

    /**
     * @var Authenticator
     */
    private $authenticator;

    protected function setUp()
    {
        $this->fooMechanism = $this->createMock(MechanismInterface::class);
        $this->fooMechanism->expects(self::any())
            ->method('getName')->willReturn('foo');

        $this->barMechanism = $this->createMock(MechanismInterface::class);
        $this->barMechanism->expects(self::any())
            ->method('getName')->willReturn('bar');

        $this->authenticator = new Authenticator([
            $this->fooMechanism,
            $this->barMechanism,
        ]);
    }

    /**
     * Authenticator should follow mechanism preference in the request.
     */
    public function testGetOrder()
    {
        $fooOnlyMechanism = $this->authenticator->get(['foo']);
        $barOnlyMechanism = $this->authenticator->get(['bar']);

        $fooBarMechanism = $this->authenticator->get(['foo', 'bar']);
        $barFooMechanism = $this->authenticator->get(['bar', 'foo']);

        $bazBarFooMechanism = $this->authenticator->get(['baz', 'bar', 'foo']);

        self::assertSame($this->fooMechanism, $fooOnlyMechanism, 'Mechanism "foo" should be chosen as the only available');
        self::assertSame($this->barMechanism, $barOnlyMechanism, 'Mechanism "bar" should be chosen as the only available');

        self::assertSame($this->fooMechanism, $fooBarMechanism, 'Mechanism "foo" should be chosen over others');
        self::assertSame($this->barMechanism, $barFooMechanism, 'Mechanism "bar" should be chosen over others');

        self::assertSame($this->barMechanism, $bazBarFooMechanism, 'Mechanism "bar" should be chosen as "baz" is not supported and "foo" is less preferable');
    }

    public function testGetException()
    {
        $this->expectException(UnsupportedSecurityMechanismException::class);

        $this->authenticator->get(['baz', 'qux']);
    }
}
