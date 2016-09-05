<?php

namespace ButterAMQP;

/**
 * Connection to AMQP server.
 */
interface ConnectionInterface
{
    /**
     * Establish connection with the server.
     *
     * @return ConnectionInterface
     */
    public function open();

    /**
     * Create a new or receive an existent channel within connection.
     *
     * Channel represents an isolated thread within connection. It allows multiple sub-processes
     * talk independently through a single socket connection.
     *
     * Most likely you don't need more channels than threads in your application.
     *
     * @param int|null $id
     *
     * @return ChannelInterface
     */
    public function channel($id = null);

    /**
     * Fetch and process notifications from server.
     *
     * @param bool $blocking
     *
     * @return ConnectionInterface
     */
    public function serve($blocking = true);

    /**
     * Close connection.
     *
     * @param int    $code
     * @param string $text
     *
     * @return ConnectionInterface
     */
    public function close($code = 0, $text = '');
}
