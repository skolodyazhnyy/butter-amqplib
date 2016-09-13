<?php

namespace ButterAMQP\AMQP091;

use ButterAMQP\ChannelInterface;
use ButterAMQP\ConsumerInterface;

class Consumer implements ConsumerInterface
{
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
     * {@inheritdoc}
     */
    public function tag()
    {
        return $this->tag;
    }

    /**
     * {@inheritdoc}
     */
    public function isActive()
    {
        return $this->channel->hasConsumer($this->tag);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->tag;
    }
}
