<?php

namespace ButterAMQP;

class Consumer implements ConsumerInterface
{
    const FLAG_NO_WAIT = 0b0000000001;
    const FLAG_EXCLUSIVE = 0b0000001000;
    const FLAG_NO_LOCAL = 0b0100000000;
    const FLAG_NO_ACK = 0b1000000000;

    /**
     * @var ChannelInterface
     */
    private $channel;

    /**
     * @var string
     */
    private $tag;

    /**
     * @param ChannelInterface $channel
     * @param string           $tag
     */
    public function __construct(ChannelInterface $channel, $tag)
    {
        $this->channel = $channel;
        $this->tag = $tag;
    }

    /**
     * {@inheritdoc}
     */
    public function cancel()
    {
        $this->channel->cancel($this->tag);

        return $this;
    }

    /**
     * @return string
     */
    public function tag()
    {
        return $this->tag;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->tag;
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return $this->channel->hasConsumer($this->tag);
    }
}
