<?php

namespace ButterAMQP;

/**
 * Exchange interface represents functional API for exchanges.
 */
interface ExchangeInterface
{
    const TYPE_TOPIC = 'topic';
    const TYPE_DIRECT = 'direct';
    const TYPE_FANOUT = 'fanout';
    const TYPE_HEADERS = 'headers';

    const FLAG_NO_WAIT = 0b00000001;
    const FLAG_DURABLE = 0b00000010;
    const FLAG_PASSIVE = 0b00000100;
    const FLAG_AUTO_DELETE = 0b00001000;
    const FLAG_INTERNAL = 0b00010000;
    const FLAG_IF_UNUSED = 0b00100000;

    /**
     * Declare an exchange.
     *
     * @param string $type
     * @param int    $flags
     * @param array  $arguments
     *
     * @return ExchangeInterface
     */
    public function define($type, $flags = 0, array $arguments = []);

    /**
     * Delete an exchange.
     *
     * @param int $flags
     *
     * @return ExchangeInterface
     */
    public function delete($flags = 0);

    /**
     * Creates exchange to exchange binding.
     *
     * @param string $exchange   destination
     * @param string $routingKey
     * @param array  $arguments
     * @param int    $flags
     *
     * @return ExchangeInterface
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
     * @return ExchangeInterface
     */
    public function unbind($exchange, $routingKey = '', array $arguments = [], $flags = 0);

    /**
     * Exchange name.
     *
     * @return string
     */
    public function name();
}
