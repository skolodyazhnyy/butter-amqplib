<?php

namespace ButterAMQP;

interface InputOutputInterface
{
    /**
     * Open connection.
     *
     * @param string $host
     * @param int    $port
     *
     * @return InputOutputInterface
     */
    public function open($host, $port);

    /**
     * Close connection.
     *
     * @return InputOutputInterface
     */
    public function close();

    /**
     * Read bytes from connection without removing them from the reading buffer.
     * Will return null if there is not enough data in the buffer, or timeout exceeded.
     *
     * @param int      $length
     * @param bool     $blocking
     * @param int|null $timeout
     *
     * @return string|null
     */
    public function peek($length, $blocking = true, $timeout = null);

    /**
     * Read bytes from connection.
     * Will return null if there is not enough data in the buffer, or timeout exceeded.
     *
     * @param int      $length
     * @param bool     $blocking
     * @param int|null $timeout
     *
     * @return string|null
     */
    public function read($length, $blocking = true, $timeout = null);

    /**
     * Writes bytes to connection.
     *
     * @param string $data
     * @param int    $length
     *
     * @return InputOutputInterface
     */
    public function write($data, $length = null);
}
