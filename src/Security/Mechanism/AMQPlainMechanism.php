<?php

namespace ButterAMQP\Security\Mechanism;

use ButterAMQP\Binary;
use ButterAMQP\Security\MechanismInterface;

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
            Binary::pack('C', strlen(self::LOGIN_KEY)),
            self::LOGIN_KEY,
            self::STRING_TYPE_HINT,
            Binary::pack('N', strlen($username)),
            $username,
            Binary::pack('C', strlen(self::PASSWORD_KEY)),
            self::PASSWORD_KEY,
            self::STRING_TYPE_HINT,
            Binary::pack('N', strlen($password)),
            $password,
        ]);
    }
}
