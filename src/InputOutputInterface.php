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
     * @return InputOutputInterface
     */
    public function write($data, $length = null);
}
