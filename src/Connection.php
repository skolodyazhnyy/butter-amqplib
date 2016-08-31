<?php

namespace AMQPLib;

use AMQPLib\Framing\Content;
use AMQPLib\Framing\Frame;
use AMQPLib\Framing\Method\ConnectionBlocked;
use AMQPLib\Framing\Method\ConnectionClose;
use AMQPLib\Framing\Method\ConnectionCloseOk;
use AMQPLib\Framing\Method\ConnectionOpen;
use AMQPLib\Framing\Method\ConnectionOpenOk;
use AMQPLib\Framing\Method\ConnectionStart;
use AMQPLib\Framing\Method\ConnectionStartOk;
use AMQPLib\Framing\Method\ConnectionTune;
use AMQPLib\Framing\Method\ConnectionTuneOk;
use AMQPLib\Framing\Method\ConnectionUnblocked;
use AMQPLib\Security\Authenticator;
use AMQPLib\Value\LongValue;
use AMQPLib\Value\ShortValue;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

class Connection implements ConnectionInterface, WireInterface, HandlerInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    const PROTOCOL_HEADER = "AMQP\x00\x00\x09\x01";
    const FRAME_ENDING = "\xCE";
    const FRAME_MAX = 32767;

    const STATUS_CLOSED = 0;
    const STATUS_READY = 1;
    const STATUS_OPEN = 2;

    const STATUS_BLOCKED = 3;

    /**
     * @var Url
     */
    private $url;

    /**
     * @var InputOutputInterface
     */
    private $io;

    /**
     * @var Authenticator
     */
    private $authenticator;

    /**
     * @var string
     */
    private $status;

    /**
     * @var Channel[]
     */
    private $channels = [];

    /**
     * @var int|null
     */
    private $frameMax;

    /**
     * @var int|null
     */
    private $channelMax;

    /**
     * @var int|null
     */
    private $heartbeat;

    /**
     * @param Url|string           $url
     * @param InputOutputInterface $io
     * @param Authenticator        $authenticator
     */
    public function __construct($url, InputOutputInterface $io, Authenticator $authenticator = null)
    {
        $this->url = $url instanceof Url ? $url : Url::parse($url);
        $this->io = $io;
        $this->authenticator = $authenticator ?: Authenticator::build();
        $this->logger = new NullLogger();
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
     * @param int $channelMax
     *
     * @return $this
     */
    public function setChannelMax($channelMax)
    {
        $this->channelMax = $channelMax;

        return $this;
    }

    /**
     * @param int $heartbeat
     *
     * @return $this
     */
    public function setHeartbeat($heartbeat)
    {
        $this->heartbeat = $heartbeat;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function open()
    {
        $this->channels = [];

        $this->status = self::STATUS_OPEN;

        $this->io->open($this->url->getHost(), $this->url->getPort());
        $this->io->write(self::PROTOCOL_HEADER);

        $this->wait(0, ConnectionTune::class);

        $this->logger->debug(sprintf('Opening virtual host "%s"', $this->url->getVhost()));

        $this->send(0, new ConnectionOpen($this->url->getVhost(), '', false))
            ->wait(0, ConnectionOpenOk::class);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function channel($id = null)
    {
        if ($id === null) {
            $id = count($this->channels) == 0 ? 1 : max(array_keys($this->channels)) + 1;
        }

        if (!is_integer($id) && $id <= 0) {
            throw new \Exception('Channel ID should be positive integer');
        }

        if (!isset($this->channels[$id])) {
            $channel = new Channel($id, $this);

            if ($channel instanceof LoggerAwareInterface) {
                $channel->setLogger($this->logger);
            }

            $this->channels[$id] = $channel;

            $channel->open();
        }

        return $this->channels[$id];
    }

    /**
     * {@inheritdoc}
     */
    public function close($code = 0, $reason = '')
    {
        $this->send(0, new ConnectionClose($code, $reason, 0, 0))
            ->wait(0, ConnectionCloseOk::class);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function serve($blocking = true, $timeout = null)
    {
        $this->next($blocking, $timeout);

        return $this;
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
    public function wait($channel, $type)
    {
        $this->logger->debug(sprintf('Waiting "%s" at channel #%d', $type, $channel), [
            'channel' => $channel,
            'frame' => $type,
        ]);

        do {
            $frame = $this->next(true);
        } while (!$frame || $frame->getChannel() != $channel || !is_a($frame, $type));

        return $frame;
    }

    /**
     * @param bool       $blocking
     * @param float|null $timeout
     *
     * @return Frame|null
     *
     * @throws \Exception
     */
    private function next($blocking = true, $timeout = null)
    {
        if (($buffer = $this->io->peek(7, $blocking, $timeout)) === null) {
            return null;
        }

        $header = unpack('Ctype/nchannel/Nsize', $buffer);

        if (($buffer = $this->io->read($header['size'] + 8, $blocking, $timeout)) === null) {
            return null;
        }

        $payload = Binary::subset($buffer, 7, -1);
        $end = Binary::subset($buffer, -1);

        if ($end != self::FRAME_ENDING) {
            throw new \Exception(sprintf('Invalid frame ending (%d)', Binary::unpack('c', $end)));
        }

        $frame = Frame::create($header['type'], $header['channel'], $payload);

        $this->logger->debug(sprintf('Receive "%s" at channel #%d', get_class($frame), $frame->getChannel()), [
            'channel' => $frame->getChannel(),
            'frame' => get_class($frame),
        ]);

        $this->getHandlerForChannel($frame->getChannel())
            ->handle($frame);

        return $frame;
    }

    /***
     * @param int $channel
     *
     * @return HandlerInterface
     */
    private function getHandlerForChannel($channel)
    {
        if ($channel === 0) {
            return $this;
        }

        return $this->channel($channel);
    }

    /**
     * {@inheritdoc}
     */
    public function handle(Frame $frame)
    {
        if ($frame instanceof ConnectionStart) {
            $this->onConnectionStart($frame);
        }

        if ($frame instanceof ConnectionTune) {
            $this->onConnectionTune($frame);
        }

        if ($frame instanceof ConnectionClose) {
            $this->onConnectionClose($frame);
        }

        if ($frame instanceof ConnectionBlocked) {
            $this->onConnectionBlocked($frame);
        }

        if ($frame instanceof ConnectionUnblocked) {
            $this->onConnectionUnblocked($frame);
        }

        if ($frame instanceof ConnectionOpenOk) {
            $this->status = self::STATUS_READY;
        }

        if ($frame instanceof ConnectionCloseOk) {
            $this->status = self::STATUS_CLOSED;
        }
    }

    /**
     * @param ConnectionStart $frame
     */
    private function onConnectionStart(ConnectionStart $frame)
    {
        $mechanism = $this->authenticator
            ->get(explode(' ', $frame->getMechanisms()));

        list($locale) = explode(' ', $frame->getLocales());

        $this->send(0, new ConnectionStartOk(
            ['product' => 'PHP AMQLib', 'version' => '0.1.0'],
            $mechanism->getName(),
            $mechanism->getResponse($this->url->getUser(), $this->url->getPass()),
            $locale
        ));
    }

    /**
     * @param ConnectionTune $frame
     */
    private function onConnectionTune(ConnectionTune $frame)
    {
        $this->channelMax = $frame->getChannelMax();
        $this->frameMax = $frame->getFrameMax();
        //$this->heartbeat = $frame->getHeartbeat();

        $this->logger->debug(sprintf(
            'Tune connection: up to %d channels, %d frame size, heartbeat every %d seconds',
            $this->channelMax,
            $this->frameMax,
            $this->heartbeat
        ));

        $this->send(0, new ConnectionTuneOk($this->channelMax, $this->frameMax, $this->heartbeat));
    }

    /**
     * @param ConnectionClose $frame
     *
     * @throws \Exception
     */
    private function onConnectionClose(ConnectionClose $frame)
    {
        $this->send(0, new ConnectionCloseOk());

        throw new \Exception($frame->getReplyText());
    }

    /**
     * @param ConnectionBlocked $frame
     */
    private function onConnectionBlocked(ConnectionBlocked $frame)
    {
        $this->status = self::STATUS_BLOCKED;
    }
    /**
     * @param ConnectionUnblocked $frame
     */
    private function onConnectionUnblocked(ConnectionUnblocked $frame)
    {
        $this->status = self::STATUS_READY;
    }
}
