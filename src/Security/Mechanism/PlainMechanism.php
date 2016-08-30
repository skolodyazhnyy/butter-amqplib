<?php

namespace AMQPLib\Security\Mechanism;

use AMQPLib\Security\MechanismInterface;

class PlainMechanism implements MechanismInterface
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'PLAIN';
    }

    /**
     * {@inheritdoc}
     */
    public function getResponse($username, $password)
    {
        return "\x00".$username."\x00".$password;
    }
}
