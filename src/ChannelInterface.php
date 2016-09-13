<?php

namespace ButterAMQP;

/**
 * Channel is an isolated thread within a connection.
 */
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
     * Process enqueued notifications for a given channel.
     *
     * @param bool $blocking
     *
     * @return ChannelInterface
     */
    public function serve($blocking = true);

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
     * Publish a message.
     *
     * @param Message $message
     * @param string  $exchange
     * @param string  $routingKey
     * @param int     $flags
     *
     * @return ChannelInterface
     */
    public function publish(Message $message, $exchange = '', $routingKey = '', $flags = 0);

    /**
     * Acknowledge a message(s).
     *
     * @param string $deliveryTag
     * @param bool   $multiple
     *
     * @return ChannelInterface
     */
    public function ack($deliveryTag, $multiple = false);

    /**
     * Reject (or negative acknowledgement) a message(s).
     *
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
     * Switch channel to confirm mode. In this mode every published message will be acknowledged
     * by the server once it takes responsibilities for it.
     *
     * Every time server sends acknowledgment callable will be invoked to handle acknowledgment.
     *
     * @see for more details see https://www.rabbitmq.com/confirms.html
     *
     * @param callable $callable will be called ever time when message is confirmed, and instance
     *                           of Confirm object will be passed as first argument
     * @param bool     $noWait   do not wait for server confirmation that channel mode changed
     *
     * @return ChannelInterface
     */
    public function selectConfirm(callable $callable, $noWait = false);

    /**
     * Switch channel to transactional mode. In this mode all acknowledgments and published messages
     * will be hold by the server until transaction is committed using ChannelInterface::txCommit()
     * or will be rejected if transaction is rolled back using ChannelInterface::txRollback().
     *
     * Transactions are atomic only within one queue.
     *
     * Some AMQP servers, like RabbitMQ, provide additional confirm mode which provide different way
     * to ensure messages are properly delivered to AMQP server and not lost on its way.
     *
     * @return ChannelInterface
     */
    public function selectTx();

    /**
     * Commit transaction. A new transaction will be started immediately.
     *
     * @return ChannelInterface
     */
    public function txCommit();

    /**
     * Rollback transaction. A new transaction will be started immediately.
     *
     * @return ChannelInterface
     */
    public function txRollback();

    /**
     * Verify if given consumers tag is registered within channel.
     *
     * @param string $tag
     *
     * @return bool
     */
    public function hasConsumer($tag);

    /**
     * Returns all registered consumers in the channel.
     *
     * @return array
     */
    public function getConsumerTags();
}
