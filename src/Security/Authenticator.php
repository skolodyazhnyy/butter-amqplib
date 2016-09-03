<?php

namespace ButterAMQP\Security;

use ButterAMQP\Exception\UnsupportedSecurityMechanismException;
use ButterAMQP\Security\Mechanism\AMQPlainMechanism;
use ButterAMQP\Security\Mechanism\PlainMechanism;

class Authenticator implements AuthenticatorInterface
{
    /**
     * @var MechanismInterface[]
     */
    private $mechanisms = [];

    /**
     * @param MechanismInterface[] $mechanisms
     */
    public function __construct(array $mechanisms)
    {
        array_walk($mechanisms, function (MechanismInterface $mechanism) {
            $this->mechanisms[$mechanism->getName()] = $mechanism;
        });
    }

    /**
     * @return Authenticator
     */
    public static function build()
    {
        return new self([
            new AMQPlainMechanism(),
            new PlainMechanism(),
        ]);
    }

    /**
     * @param array $mechanisms
     *
     * @return MechanismInterface
     *
     * @throws \Exception
     */
    public function get(array $mechanisms)
    {
        foreach ($mechanisms as $mechanism) {
            if (isset($this->mechanisms[$mechanism])) {
                return $this->mechanisms[$mechanism];
            }
        }

        throw new UnsupportedSecurityMechanismException(sprintf(
            'Non of the mechanisms "%s" is supported',
            implode('", "', $mechanisms)
        ));
    }
}
