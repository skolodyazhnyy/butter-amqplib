<?php

namespace ButterAMQP;

interface ConsumerInterface
{
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
