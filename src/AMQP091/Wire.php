<?php

namespace ButterAMQP\AMQP091;

use ButterAMQP\Binary;
use ButterAMQP\Buffer;
use ButterAMQP\Exception\InvalidFrameEndingException;
use ButterAMQP\AMQP091\Framing\Content;
use ButterAMQP\AMQP091\Framing\Frame;
use ButterAMQP\AMQP091\Framing\Heartbeat;
use ButterAMQP\Heartbeat\NullHeartbeat;
use ButterAMQP\HeartbeatInterface;
use ButterAMQP\IOInterface;
use ButterAMQP\Url;
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
     * @var string
     */
    private $buffer;

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
    public function open(Url $url)
    {
        $this->subscribers = [];
        $this->buffer = '';

        $this->io->open(
            $this->getProtocolForScheme($url),
            $url->getHost(),
            $url->getPort(),
            $url->getQuery()
        );

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
            $this->send(new Heartbeat(0));
        }

        if ($frame = $this->tryNext()) {
            $this->heartbeat->serverBeat();
            $this->dispatch($frame);
        } else {
            $this->read($blocking);
        }

        return $frame;
    }

    /**
     * Read next chunk of the data from the buffer.
     *
     * @param bool $blocking
     */
    private function read($blocking)
    {
        $this->buffer .= $this->io->read($this->frameMax ?: 8192, $blocking);
    }

    /**
     * Tries to fetch next frame from reading buffer.
     * Will return a Frame or null if there is not enough data in the buffer.
     *
     * @return Frame|null
     *
     * @throws InvalidFrameEndingException
     */
    private function tryNext()
    {
        $length = strlen($this->buffer);

        if ($length < 7) {
            return null;
        }

        $header = unpack('Ctype/nchannel/Nsize', $this->buffer);
        $size = $header['size'] + 8;

        if ($length < $size) {
            return null;
        }

        if ($length == $size) {
            $buffer = $this->buffer;
            $this->buffer = '';
        } else {
            $buffer = substr($this->buffer, 0, $size);
            $this->buffer = substr($this->buffer, $size, $length - $size);
        }

        $end = $buffer[$size - 1];

        if ($end != self::FRAME_ENDING) {
            throw new InvalidFrameEndingException(sprintf('Invalid frame ending (%d)', Binary::unpack('c', $end)));
        }

        return Frame::decode(new Buffer($buffer));
    }

    /**
     * @param Frame $frame
     */
    private function dispatch(Frame $frame)
    {
        if ($subscriber = $this->getSubscriber($frame->getChannel())) {
            $subscriber->dispatch($frame);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function send(Frame $frame)
    {
        $this->heartbeat->clientBeat();

        foreach ($this->chop($frame) as $piece) {
            $this->io->write($piece->encode());
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
        $chunks = ceil(strlen($data) / $size);

        for ($c = 0; $c < $chunks; ++$c) {
            $frames[] = new Content($frame->getChannel(), substr($data, $c * $size, $size));
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

        do {
            $frame = $this->next(true);
        } while (!$this->isFrameMatch($frame, $channel, $types));

        return $frame;
    }

    /**
     * @param Frame $frame
     * @param int   $channel
     * @param array $types
     *
     * @return bool
     */
    private function isFrameMatch(Frame $frame = null, $channel = 0, array $types = [])
    {
        return $frame && $frame->getChannel() == $channel && in_array(get_class($frame), $types);
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

    /**
     * @param Url $url
     *
     * @return string
     */
    private function getProtocolForScheme(Url $url)
    {
        return strcasecmp($url->getScheme(), 'amqps') == 0 ? 'ssl' : 'tcp';
    }
}
