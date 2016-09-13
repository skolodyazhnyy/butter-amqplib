<?php

namespace ButterAMQP;

interface ConsumerInterface
{
    const FLAG_NO_WAIT = 0b0000000001;
    const FLAG_EXCLUSIVE = 0b0000001000;
    const FLAG_NO_LOCAL = 0b0100000000;
    const FLAG_NO_ACK = 0b1000000000;

    /**
     * Cancel consuming.
     *
     * @return ConsumerInterface
     */
    public function cancel();

    /**
     * Returns consumer's tag.
     *
     * @return string
     */
    public function tag();

    /**
     * @return bool
     */
    public function isActive();
}
