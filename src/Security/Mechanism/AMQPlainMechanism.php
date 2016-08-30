<?php

namespace AMQPLib\Security\Mechanism;

use AMQPLib\Binary;
use AMQPLib\Security\MechanismInterface;

class AMQPlainMechanism implements MechanismInterface
{
    const LOGIN_KEY = 'LOGIN';
    const PASSWORD_KEY = 'PASSWORD';
    const STRING_TYPE_HINT = 'S';

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'AMQPLAIN';
    }

    /**
     * {@inheritdoc}
     */
    public function getResponse($username, $password)
    {
        return implode('', [
            Binary::pack('C', Binary::length(self::LOGIN_KEY)),
            self::LOGIN_KEY,
            self::STRING_TYPE_HINT,
            Binary::pack('N', Binary::length($username)),
            $username,
            Binary::pack('C', Binary::length(self::PASSWORD_KEY)),
            self::PASSWORD_KEY,
            self::STRING_TYPE_HINT,
            Binary::pack('N', Binary::length($username)),
            $password,
        ]);
    }
}
