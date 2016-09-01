<?php

namespace AMQLib\Security;

use AMQLib\Security\Mechanism\AMQPlainMechanism;
use AMQLib\Security\Mechanism\PlainMechanism;

class Authenticator
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

        throw new \Exception(sprintf('Non of the mechanisms "%s" is supported', implode('", "', $mechanisms)));
    }
}
