<?php

namespace ButterAMQP\Debug;

use ButterAMQP\AMQP091\Framing\Frame;
use ButterAMQP\HeartbeatInterface;
use ButterAMQP\Url;
use ButterAMQP\AMQP091\WireInterface;
use ButterAMQP\AMQP091\WireSubscriberInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;

class LoggerDecoratedWire implements WireInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var WireInterface
     */
    private $wire;

    /**
     * @param WireInterface   $wire
     * @param LoggerInterface $logger
     */
    public function __construct(WireInterface $wire, LoggerInterface $logger)
    {
        $this->wire = $wire;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function setHeartbeat(HeartbeatInterface $heartbeat)
    {
        $this->wire->setHeartbeat($heartbeat);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setFrameMax($frameMax)
    {
        $this->wire->setFrameMax($frameMax);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function open(Url $url)
    {
        $this->wire->open($url);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function next($blocking = true)
    {
        return $this->wire->next();
    }

    /**
     * {@inheritdoc}
     */
    public function send(Frame $frame)
    {
        $this->logger->info(sprintf('Sending frame "%s" to channel #%d', get_class($frame), $frame->getChannel()), [
            'frame' => $frame,
        ]);

        $this->wire->send($frame);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function wait($channel, $types)
    {
        $typeString = is_array($types) ? implode('", "', $types) : $types;

        $this->logger->info(sprintf('Waiting for "%s" at channel #%d', $typeString, $channel));

        return $this->wire->wait($channel, $types);
    }

    /**
     * {@inheritdoc}
     */
    public function subscribe($channel, WireSubscriberInterface $handler)
    {
        $this->wire->subscribe($channel, $handler);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        $this->wire->close();

        return $this;
    }
}
