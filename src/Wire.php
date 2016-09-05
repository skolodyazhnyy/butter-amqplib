<?php

namespace ButterAMQP;

use ButterAMQP\Exception\InvalidFrameEndingException;
use ButterAMQP\Framing\Content;
use ButterAMQP\Framing\Frame;
use ButterAMQP\Framing\Heartbeat;
use ButterAMQP\Heartbeat\NullHeartbeat;
use ButterAMQP\Value\LongValue;
use ButterAMQP\Value\ShortValue;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

class Wire implements WireInterface, LoggerAwareInterface
{
    const PROTOCOL_HEADER = "AMQP\x00\x00\x09\x01";
    const FRAME_ENDING = "\xCE";

    use LoggerAwareTrait;

    /**
     * @var IOInterface
     */
    private $io;

    /**
     * @var WireSubscriberInterface[]
     */
    private $subscribers = [];

    /**
     * @var HeartbeatInterface
     */
    private $heartbeat;

    /**
     * @var int
     */
    private $frameMax;

    /**
     * @param IOInterface $io
     */
    public function __construct(IOInterface $io)
    {
        $this->io = $io;
        $this->logger = new NullLogger();
        $this->heartbeat = new NullHeartbeat();
    }

    /**
     * {@inheritdoc}
     */
    public function open($host, $port)
    {
        $this->io->open($host, $port);
        $this->io->write(self::PROTOCOL_HEADER);

        // @todo: peek next 8 bytes and check if its a frame or "wrong protocol" reply

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function next($blocking = true)
    {
        if ($this->heartbeat->shouldSendHeartbeat()) {
            $this->send(0, new Heartbeat());
        }

        if (($buffer = $this->io->peek(7, $blocking)) === null) {
            return null;
        }

        $header = unpack('Ctype/nchannel/Nsize', $buffer);

        if (($buffer = $this->io->read($header['size'] + 8, $blocking)) === null) {
            return null;
        }

        $payload = Binary::subset($buffer, 7, -1);
        $end = Binary::subset($buffer, -1);

        if ($end != self::FRAME_ENDING) {
            throw new InvalidFrameEndingException(sprintf('Invalid frame ending (%d)', Binary::unpack('c', $end)));
        }

        $frame = Frame::create($header['type'], $header['channel'], $payload);

        $this->logger->debug(sprintf('Receive "%s" at channel #%d', get_class($frame), $frame->getChannel()), [
            'channel' => $frame->getChannel(),
            'frame' => get_class($frame),
        ]);

        if ($subscriber = $this->getSubscriber($frame->getChannel())) {
            $subscriber->dispatch($frame);
        }

        $this->heartbeat->serverBeat();

        return $frame;
    }

    /**
     * {@inheritdoc}
     */
    public function send($channel, Frame $frame)
    {
        $this->logger->debug(sprintf('Sending "%s" to channel #%d', get_class($frame), $channel), [
            'channel' => $channel,
            'frame' => get_class($frame),
        ]);

        $this->heartbeat->clientBeat();

        foreach ($this->chop($frame) as $piece) {
            $data = $piece->encode();

            $this->io->write(
                $piece->getFrameType().
                ShortValue::encode($channel).
                LongValue::encode(Binary::length($data)).
                $data.
                self::FRAME_ENDING
            );
        }

        return $this;
    }

    /**
     * @param Frame $frame
     *
     * @return array
     */
    private function chop(Frame $frame)
    {
        if (!$this->frameMax || !$frame instanceof Content) {
            return [$frame];
        }

        $frames = [];
        $data = $frame->getData();
        $size = $this->frameMax - 8;
        $chunks = ceil(Binary::length($data) / $size);

        for ($c = 0; $c < $chunks; ++$c) {
            $frames[] = new Content(Binary::subset($data, $c * $size, $size));
        }

        return $frames;
    }

    /**
     * {@inheritdoc}
     */
    public function wait($channel, $types)
    {
        if (!is_array($types)) {
            $types = [$types];
        }

        $this->logger->debug(sprintf('Waiting "%s" at channel #%d', implode('", "', $types), $channel), [
            'channel' => $channel,
            'frame' => $types,
        ]);

        do {
            $frame = $this->next(true);

            if (!$frame || $frame->getChannel() != $channel) {
                continue;
            }

            foreach ($types as $type) {
                if (is_a($frame, $type)) {
                    return $frame;
                }
            }
        } while (true);

        return $frame;
    }

    /**
     * {@inheritdoc}
     */
    public function subscribe($channel, WireSubscriberInterface $handler)
    {
        $this->subscribers[$channel] = $handler;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        $this->io->close();

        return $this;
    }

    /**
     * @param HeartbeatInterface $heartbeat
     *
     * @return $this
     */
    public function setHeartbeat(HeartbeatInterface $heartbeat)
    {
        $this->heartbeat = $heartbeat;

        return $this;
    }

    /**
     * @param int $frameMax
     *
     * @return $this
     */
    public function setFrameMax($frameMax)
    {
        $this->frameMax = $frameMax;

        return $this;
    }

    /**
     * @param int $channel
     *
     * @return WireSubscriberInterface|null
     */
    private function getSubscriber($channel)
    {
        return isset($this->subscribers[$channel]) ? $this->subscribers[$channel] : null;
    }
}
