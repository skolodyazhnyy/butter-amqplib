<?php

namespace AMQLib;

interface QueueInterface
{
    /**
     * Declare a queue.
     *
     * @param int   $flags
     * @param array $arguments
     *
     * @return QueueInterface
     */
    public function define($flags = 0, array $arguments = []);

    /**
     * Delete a queue.
     *
     * @param int $flags
     *
     * @return QueueInterface
     */
    public function delete($flags = 0);

    /**
     * Bind a queue to exchange.
     *
     * @param string $exchange
     * @param string $routingKey
     * @param array  $arguments
     * @param int    $flags
     *
     * @return QueueInterface
     */
    public function bind($exchange, $routingKey = '', array $arguments = [], $flags = 0);

    /**
     * Unbind a queue from exchange.
     *
     * @param string $exchange
     * @param string $routingKey
     * @param array  $arguments
     *
     * @return QueueInterface
     */
    public function unbind($exchange, $routingKey = '', array $arguments = []);

    /**
     * Purge queue.
     *
     * @param int $flags
     *
     * @return QueueInterface
     */
    public function purge($flags = 0);

    /**
     * @param callable $callback
     * @param int      $flags
     * @param string   $tag
     * @param array    $arguments
     *
     * @return ConsumerInterface
     */
    public function consume(callable $callback, $flags = 0, $tag = '', array $arguments = []);

    /**
     * Returns queue name. Can be used after declaring an anonymous queue.
     * Normally gets populated after declaring the queue.
     * This method may return null if information is not yet fetched or not provided by the server.
     *
     * @return string|null
     */
    public function name();

    /**
     * Returns last known number of messages in the queue.
     * Normally gets populated after declaring, purging or deleting the queue.
     * This method may return null if information is not yet fetched or not provided by the server.
     *
     * @return int|null
     */
    public function messagesCount();

    /**
     * Returns last known number of consumers assigned to the queue.
     * Normally gets populated after declaring the queue.
     * This method may return null if information is not yet fetched or not provided by the server.
     *
     * @return int|null
     */
    public function consumerCount();
}
