<?php

namespace AMQPLib;

interface ChannelInterface
{
    /**
     * Open channel.
     *
     * @return $this
     */
    public function open();

    /**
     * Activate or deactivate channel.
     *
     * @param bool $active
     *
     * @return $this
     */
    public function flow($active);

    /**
     * Close channel.
     *
     * @return $this
     */
    public function close();

    /**
     * Specify quality of service for the channel.
     *
     * @param int $prefetchSize
     * @param int $prefetchCount
     *
     * @return $this
     */
    public function qos($prefetchSize, $prefetchCount);

    /**
     * Returns interface to work with an exchange.
     *
     * @param string $name
     *
     * @return ExchangeInterface
     */
    public function exchange($name);

    /**
     * Returns interface to work with a queue.
     *
     * @param string $name
     *
     * @return QueueInterface
     */
    public function queue($name = '');

    /**
     * Declare consumer for a queue.
     *
     * @param string   $queue
     * @param callable $callback
     * @param int      $flags
     * @param string   $tag
     * @param array    $arguments
     *
     * @return ConsumerInterface
     */
    public function consume($queue, callable $callback, $flags = 0, $tag = '', array $arguments = []);

    /**
     * Cancel consuming.
     *
     * @param $tag
     *
     * @return $this
     */
    public function cancel($tag);

    /**
     * @param Message $message
     * @param string  $exchange
     * @param string  $routingKey
     * @param int     $flags
     *
     * @return $this
     */
    public function publish(Message $message, $exchange = '', $routingKey = '', $flags = 0);

    /**
     * @param string $deliveryTag
     * @param bool   $multiple
     *
     * @return $this
     */
    public function ack($deliveryTag, $multiple = false);

    /**
     * @param string $deliveryTag
     * @param bool   $requeue
     *
     * @return $this
     */
    public function reject($deliveryTag, $requeue = true);
    /*
     * Process incoming messages.
     *
     * @return $this
     */
    //public function serve();
}
