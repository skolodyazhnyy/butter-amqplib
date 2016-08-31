<?php

namespace AMQLib\Framing;

use AMQLib\Buffer;

abstract class Frame
{
    /**
     * @var int
     */
    private $channel;

    /**
     * @return int
     */
    public function getChannel()
    {
        return $this->channel;
    }

    /**
     * @todo insert channel through constructor
     *
     * @param int $channel
     *
     * @return $this
     */
    public function setChannel($channel)
    {
        $this->channel = $channel;

        return $this;
    }

    /**
     * @return string
     */
    abstract public function getFrameType();

    /**
     * @return string
     */
    abstract public function encode();

    /**
     * @param int    $type
     * @param int    $channel
     * @param string $payload
     *
     * @return Frame
     *
     * @throws \Exception
     */
    public static function create($type, $channel, $payload)
    {
        $buffer = new Buffer($payload);

        switch ($type) {
            case 1: return Method::decode($buffer)->setChannel($channel);
            case 2: return Header::decode($buffer)->setChannel($channel);
            case 3: return Content::decode($buffer)->setChannel($channel);
            case 8: return Heartbeat::decode($buffer)->setChannel($channel);
        }

        throw new \Exception(sprintf('Invalid frame type (%d)', $type));
    }
}
