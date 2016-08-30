<?php

namespace AMQPLib;

interface ConnectionInterface
{
    /**
     * Establish connection with the server.
     *
     * @return $this
     */
    public function open();

    /**
     * Create a channel within connection.
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
     * @param null $timeout
     *
     * @return $this
     */
    public function serve($blocking = true, $timeout = null);

    /**
     * Close connection.
     *
     * @param int    $code
     * @param string $text
     *
     * @return $this
     */
    public function close($code = 0, $text = '');
}
