<?php

namespace AMQPLib;

interface ExchangeInterface
{
    /**
     * Declare an exchange.
     *
     * @param string $type
     * @param int    $flags
     * @param array  $arguments
     *
     * @return $this
     */
    public function define($type, $flags = 0, array $arguments = []);

    /**
     * Delete an exchange.
     *
     * @param int $flags
     *
     * @return $this
     */
    public function delete($flags = 0);

    /**
     * Publish a message.
     *
     * @param Message $message
     * @param string  $routingKey
     * @param int     $flags
     *
     * @return $this
     */
    public function publish(Message $message, $routingKey = '', $flags = 0);

    /**
     * Creates exchange to exchange binding.
     *
     * @param string $exchange   destination
     * @param string $routingKey
     * @param array  $arguments
     * @param int    $flags
     *
     * @return $this
     */
    public function bind($exchange, $routingKey = '', array $arguments = [], $flags = 0);

    /**
     * Remove exchange to exchange binding.
     *
     * @param string $exchange
     * @param string $routingKey
     * @param array  $arguments
     * @param int    $flags
     *
     * @return $this
     */
    public function unbind($exchange, $routingKey = '', array $arguments = [], $flags = 0);

    /**
     * Exchange name.
     *
     * @return string
     */
    public function name();
}
