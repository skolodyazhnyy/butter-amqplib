<?php

namespace ButterAMQP;

interface IOInterface
{
    /**
     * Open connection.
     *
     * @param string $host
     * @param int    $port
     *
     * @return IOInterface
     */
    public function open($host, $port);

    /**
     * Close connection.
     *
     * @return IOInterface
     */
    public function close();

    /**
     * Read bytes from connection without removing them from the reading buffer.
     * Will return null if there is not enough data in the buffer, or timeout exceeded.
     *
     * @param int  $length
     * @param bool $blocking
     *
     * @return string|null
     */
    public function peek($length, $blocking = true);

    /**
     * Read bytes from connection.
     * Will return null if there is not enough data in the buffer, or timeout exceeded.
     *
     * @param int  $length
     * @param bool $blocking
     *
     * @return string|null
     */
    public function read($length, $blocking = true);

    /**
     * Writes bytes to connection.
     *
     * @param string $data
     * @param int    $length
     *
     * @return IOInterface
     */
    public function write($data, $length = null);
}
