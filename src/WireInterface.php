<?php

namespace AMQLib;

use AMQLib\Framing\Frame;

/**
 * Wire represents a framing level connection to the server.
 *
 * It operates with frames rather than individual bytes, maintain connection health,
 * dispatches server notifications to their handlers.
 */
interface WireInterface
{
    /**
     * Set heartbeat for the wire.
     *
     * @param HeartbeatInterface $heartbeat
     *
     * @return WireInterface
     */
    public function setHeartbeat(HeartbeatInterface $heartbeat);

    /**
     * Set maximum size of the content frame.
     *
     * @param int $frameMax
     *
     * @return WireInterface
     */
    public function setFrameMax($frameMax);

    /**
     * Opens connection to given host and port.
     *
     * @param string $host
     * @param string $port
     *
     * @return WireInterface
     */
    public function open($host, $port);

    /**
     * Fetches next frame from the server.
     *
     * Returned values:
     *  - Method will return next fully available Frame
     *  - Method will return null if no frame is fully available and reading is non-blocking
     *  - Method will return null if no frame is fully available, reading is blocking but timeout is reached
     *
     * Example 1: Get a frame if any available otherwise return control immediately
     *
     *   $wire->next(false, 0); // will return null or Frame
     *
     * Example 2: Wait for a frame for at most 2.5 seconds and return control afterwards
     *
     *   $wire->next(true, 2.5); // will return null or Frame
     *
     * Example 3: Block execution until any Frame is received
     *
     *   $wire->next(true, 0); // will always return a frame
     *
     *
     * @param bool      $blocking specify if reading should block execution
     * @param float|int $timeout  timeout for reading
     *
     * @return Frame|null
     */
    public function next($blocking = true, $timeout = 0);

    /**
     * Send a frame to the connection.
     *
     * @param int   $channel
     * @param Frame $frame
     *
     * @return WireInterface
     */
    public function send($channel, Frame $frame);

    /**
     * Wait for a given type of the frame in the given channel.
     * Returned value always will be instance of given class name.
     *
     * Example: Wait for ChannelFlowOk frame at channel 5 to arrive.
     *
     *   $frame = $wire->wait(5, ChannelFlowOk::class);
     *
     *
     * @param int    $channel channel to watch
     * @param string $type    frame class name
     *
     * @return Frame
     */
    public function wait($channel, $type);

    /**
     * Set frame handler for a given channel.
     *
     * There can be only one subscriber defined per channel.
     * Whenever a new frame for a given channel is fetched it will be passed to subscriber immediately.
     *
     * @param int                     $channel
     * @param WireSubscriberInterface $handler
     *
     * @return WireInterface
     */
    public function subscribe($channel, WireSubscriberInterface $handler);

    /**
     * Close connection.
     *
     * @return WireInterface
     */
    public function close();
}
