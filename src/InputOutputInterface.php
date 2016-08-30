<?php

namespace AMQPLib;

interface InputOutputInterface
{
    /**
     * Open connection.
     *
     * @param string $host
     * @param int    $port
     *
     * @return $this
     */
    public function open($host, $port);

    /**
     * Close connection.
     *
     * @return $this
     */
    public function close();

    /**
     * Read bytes from connection.
     *
     * @param int $length
     *
     * @return string
     */
    public function read($length);

    /**
     * Writes bytes to connection.
     *
     * @param string $data
     * @param int    $length
     *
     * @return $this
     */
    public function write($data, $length = null);
}
