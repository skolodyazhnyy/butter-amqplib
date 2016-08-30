<?php

namespace AMQPLib;

interface ConsumerInterface
{
    /**
     * Cancel consuming.
     *
     * @return $this
     */
    public function cancel();

    /**
     * Returns consumer's tag.
     *
     * @return string
     */
    public function tag();
}
