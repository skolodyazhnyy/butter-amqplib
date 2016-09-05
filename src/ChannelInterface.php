<?php

namespace ButterAMQP;

interface ChannelInterface
{
    /**
     * Open channel.
     *
     * @return ChannelInterface
     */
    public function open();

    /**
     * Activate or deactivate channel.
     *
     * @param bool $active
     *
     * @return ChannelInterface
     */
    public function flow($active);

    /**
     * Close channel.
     *
     * @return ChannelInterface
     */
    public function close();

    /**
     * Specify quality of service for the channel.
     *
     * @param int  $prefetchSize
     * @param int  $prefetchCount
     * @param bool $globally
     *
     * @return ChannelInterface
     */
    public function qos($prefetchSize, $prefetchCount, $globally = false);

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
     * Fetch a single message directly from the queue.
     *
     * @param string $queue
     * @param bool   $withAck
     *
     * @return Delivery|null
     */
    public function get($queue, $withAck = true);

    /**
     * Re-deliver all messages assigned to this consumer but not acknowledged.
     *
     * @param bool $requeue
     *
     * @return ChannelInterface
     */
    public function recover($requeue = true);

    /**
     * Cancel consuming.
     *
     * @param string $tag
     *
     * @return ChannelInterface
     */
    public function cancel($tag);

    /**
     * @param Message $message
     * @param string  $exchange
     * @param string  $routingKey
     * @param int     $flags
     *
     * @return ChannelInterface
     */
    public function publish(Message $message, $exchange = '', $routingKey = '', $flags = 0);

    /**
     * @param string $deliveryTag
     * @param bool   $multiple
     *
     * @return ChannelInterface
     */
    public function ack($deliveryTag, $multiple = false);

    /**
     * @param string $deliveryTag
     * @param bool   $requeue
     * @param bool   $multiple
     *
     * @return ChannelInterface
     */
    public function reject($deliveryTag, $requeue = true, $multiple = false);

    /**
     * Set a callback to handle returned messages.
     *
     * @param callable $callable
     *
     * @return ChannelInterface
     */
    public function onReturn(callable $callable);

    /**
     * @param string $tag
     *
     * @return bool
     */
    public function hasConsumer($tag);

    /**
     * @return array
     */
    public function getConsumerTags();
}
