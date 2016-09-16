<?php

namespace ButterAMQP\Security\Mechanism;

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
            pack('C', strlen(self::LOGIN_KEY)),
            self::LOGIN_KEY,
            self::STRING_TYPE_HINT,
            pack('N', strlen($username)),
            $username,
            pack('C', strlen(self::PASSWORD_KEY)),
            self::PASSWORD_KEY,
            self::STRING_TYPE_HINT,
            pack('N', strlen($password)),
            $password,
        ]);
    }
}
