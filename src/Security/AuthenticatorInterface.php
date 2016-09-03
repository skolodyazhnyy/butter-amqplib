<?php

namespace ButterAMQP\Security;

interface AuthenticatorInterface
{
    /**
     * @param array $mechanisms
     *
     * @return MechanismInterface
     */
    public function get(array $mechanisms);
}
