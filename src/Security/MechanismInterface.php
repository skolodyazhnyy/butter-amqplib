<?php

namespace ButterAMQP\Security;

interface MechanismInterface
{
    /**
     * @return string
     */
    public function getName();

    /**
     * @param string $username
     * @param string $password
     *
     * @return string
     */
    public function getResponse($username, $password);
}
