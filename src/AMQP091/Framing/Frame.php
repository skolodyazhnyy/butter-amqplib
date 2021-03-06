<?php
/*
 * This file is automatically generated.
 */

namespace ButterAMQP\AMQP091\Framing;

use ButterAMQP\Buffer;
use ButterAMQP\Binary;
use ButterAMQP\Value;

/**
 * @codeCoverageIgnore
 */
abstract class Frame
{
    /**
     * @var int
     */
    protected $channel;

    /**
     * @param int $channel
     */
    public function __construct($channel)
    {
        $this->channel = $channel;
    }

    /**
     * @return int
     */
    public function getChannel()
    {
        return $this->channel;
    }

    /**
     * @return string
     */
    abstract public function encode();

    /**
     * @param array  $header
     * @param Buffer $data
     *
     * @return $this
     */
    public static function decode(array $header, Buffer $data)
    {
        if ($header['type'] === 1) {
            $class = $data->read(2);
            $method = $data->read(2);

            if ($class === "\x00\x0A") {
                if ($method === "\x00\x0A") {
                    return new Method\ConnectionStart(
                        $header['channel'],
                        Value\OctetValue::decode($data),
                        Value\OctetValue::decode($data),
                        Value\TableValue::decode($data),
                        Value\LongStringValue::decode($data),
                        Value\LongStringValue::decode($data)
                    );
                }
                if ($method === "\x00\x0B") {
                    return new Method\ConnectionStartOk(
                        $header['channel'],
                        Value\TableValue::decode($data),
                        Value\ShortStringValue::decode($data),
                        Value\LongStringValue::decode($data),
                        Value\ShortStringValue::decode($data)
                    );
                }
                if ($method === "\x00\x14") {
                    return new Method\ConnectionSecure(
                        $header['channel'],
                        Value\LongStringValue::decode($data)
                    );
                }
                if ($method === "\x00\x15") {
                    return new Method\ConnectionSecureOk(
                        $header['channel'],
                        Value\LongStringValue::decode($data)
                    );
                }
                if ($method === "\x00\x1E") {
                    return new Method\ConnectionTune(
                        $header['channel'],
                        Value\ShortValue::decode($data),
                        Value\LongValue::decode($data),
                        Value\ShortValue::decode($data)
                    );
                }
                if ($method === "\x00\x1F") {
                    return new Method\ConnectionTuneOk(
                        $header['channel'],
                        Value\ShortValue::decode($data),
                        Value\LongValue::decode($data),
                        Value\ShortValue::decode($data)
                    );
                }
                if ($method === "\x00\x28") {
                    return new Method\ConnectionOpen(
                        $header['channel'],
                        Value\ShortStringValue::decode($data),
                        Value\ShortStringValue::decode($data),
                        Value\BooleanValue::decode($data)
                    );
                }
                if ($method === "\x00\x29") {
                    return new Method\ConnectionOpenOk(
                        $header['channel'],
                        Value\ShortStringValue::decode($data)
                    );
                }
                if ($method === "\x00\x32") {
                    return new Method\ConnectionClose(
                        $header['channel'],
                        Value\ShortValue::decode($data),
                        Value\ShortStringValue::decode($data),
                        Value\ShortValue::decode($data),
                        Value\ShortValue::decode($data)
                    );
                }
                if ($method === "\x00\x33") {
                    return new Method\ConnectionCloseOk(
                        $header['channel']
                    );
                }
                if ($method === "\x00\x3C") {
                    return new Method\ConnectionBlocked(
                        $header['channel'],
                        Value\ShortStringValue::decode($data)
                    );
                }
                if ($method === "\x00\x3D") {
                    return new Method\ConnectionUnblocked(
                        $header['channel']
                    );
                }
            }

            if ($class === "\x00\x14") {
                if ($method === "\x00\x0A") {
                    return new Method\ChannelOpen(
                        $header['channel'],
                        Value\ShortStringValue::decode($data)
                    );
                }
                if ($method === "\x00\x0B") {
                    return new Method\ChannelOpenOk(
                        $header['channel'],
                        Value\LongStringValue::decode($data)
                    );
                }
                if ($method === "\x00\x14") {
                    return new Method\ChannelFlow(
                        $header['channel'],
                        Value\BooleanValue::decode($data)
                    );
                }
                if ($method === "\x00\x15") {
                    return new Method\ChannelFlowOk(
                        $header['channel'],
                        Value\BooleanValue::decode($data)
                    );
                }
                if ($method === "\x00\x28") {
                    return new Method\ChannelClose(
                        $header['channel'],
                        Value\ShortValue::decode($data),
                        Value\ShortStringValue::decode($data),
                        Value\ShortValue::decode($data),
                        Value\ShortValue::decode($data)
                    );
                }
                if ($method === "\x00\x29") {
                    return new Method\ChannelCloseOk(
                        $header['channel']
                    );
                }
            }

            if ($class === "\x00\x28") {
                if ($method === "\x00\x0A") {
                    return new Method\ExchangeDeclare(
                        $header['channel'],
                        Value\ShortValue::decode($data),
                        Value\ShortStringValue::decode($data),
                        Value\ShortStringValue::decode($data),
                        (bool) ($flags = Value\OctetValue::decode($data)) & 1,
                        (bool) $flags & 2,
                        (bool) $flags & 4,
                        (bool) $flags & 8,
                        (bool) $flags & 16,
                        Value\TableValue::decode($data)
                    );
                }
                if ($method === "\x00\x0B") {
                    return new Method\ExchangeDeclareOk(
                        $header['channel']
                    );
                }
                if ($method === "\x00\x14") {
                    return new Method\ExchangeDelete(
                        $header['channel'],
                        Value\ShortValue::decode($data),
                        Value\ShortStringValue::decode($data),
                        (bool) ($flags = Value\OctetValue::decode($data)) & 1,
                        (bool) $flags & 2
                    );
                }
                if ($method === "\x00\x15") {
                    return new Method\ExchangeDeleteOk(
                        $header['channel']
                    );
                }
                if ($method === "\x00\x1E") {
                    return new Method\ExchangeBind(
                        $header['channel'],
                        Value\ShortValue::decode($data),
                        Value\ShortStringValue::decode($data),
                        Value\ShortStringValue::decode($data),
                        Value\ShortStringValue::decode($data),
                        Value\BooleanValue::decode($data),
                        Value\TableValue::decode($data)
                    );
                }
                if ($method === "\x00\x1F") {
                    return new Method\ExchangeBindOk(
                        $header['channel']
                    );
                }
                if ($method === "\x00\x28") {
                    return new Method\ExchangeUnbind(
                        $header['channel'],
                        Value\ShortValue::decode($data),
                        Value\ShortStringValue::decode($data),
                        Value\ShortStringValue::decode($data),
                        Value\ShortStringValue::decode($data),
                        Value\BooleanValue::decode($data),
                        Value\TableValue::decode($data)
                    );
                }
                if ($method === "\x00\x33") {
                    return new Method\ExchangeUnbindOk(
                        $header['channel']
                    );
                }
            }

            if ($class === "\x00\x32") {
                if ($method === "\x00\x0A") {
                    return new Method\QueueDeclare(
                        $header['channel'],
                        Value\ShortValue::decode($data),
                        Value\ShortStringValue::decode($data),
                        (bool) ($flags = Value\OctetValue::decode($data)) & 1,
                        (bool) $flags & 2,
                        (bool) $flags & 4,
                        (bool) $flags & 8,
                        (bool) $flags & 16,
                        Value\TableValue::decode($data)
                    );
                }
                if ($method === "\x00\x0B") {
                    return new Method\QueueDeclareOk(
                        $header['channel'],
                        Value\ShortStringValue::decode($data),
                        Value\LongValue::decode($data),
                        Value\LongValue::decode($data)
                    );
                }
                if ($method === "\x00\x14") {
                    return new Method\QueueBind(
                        $header['channel'],
                        Value\ShortValue::decode($data),
                        Value\ShortStringValue::decode($data),
                        Value\ShortStringValue::decode($data),
                        Value\ShortStringValue::decode($data),
                        Value\BooleanValue::decode($data),
                        Value\TableValue::decode($data)
                    );
                }
                if ($method === "\x00\x15") {
                    return new Method\QueueBindOk(
                        $header['channel']
                    );
                }
                if ($method === "\x00\x32") {
                    return new Method\QueueUnbind(
                        $header['channel'],
                        Value\ShortValue::decode($data),
                        Value\ShortStringValue::decode($data),
                        Value\ShortStringValue::decode($data),
                        Value\ShortStringValue::decode($data),
                        Value\TableValue::decode($data)
                    );
                }
                if ($method === "\x00\x33") {
                    return new Method\QueueUnbindOk(
                        $header['channel']
                    );
                }
                if ($method === "\x00\x1E") {
                    return new Method\QueuePurge(
                        $header['channel'],
                        Value\ShortValue::decode($data),
                        Value\ShortStringValue::decode($data),
                        Value\BooleanValue::decode($data)
                    );
                }
                if ($method === "\x00\x1F") {
                    return new Method\QueuePurgeOk(
                        $header['channel'],
                        Value\LongValue::decode($data)
                    );
                }
                if ($method === "\x00\x28") {
                    return new Method\QueueDelete(
                        $header['channel'],
                        Value\ShortValue::decode($data),
                        Value\ShortStringValue::decode($data),
                        (bool) ($flags = Value\OctetValue::decode($data)) & 1,
                        (bool) $flags & 2,
                        (bool) $flags & 4
                    );
                }
                if ($method === "\x00\x29") {
                    return new Method\QueueDeleteOk(
                        $header['channel'],
                        Value\LongValue::decode($data)
                    );
                }
            }

            if ($class === "\x00\x3C") {
                if ($method === "\x00\x0A") {
                    return new Method\BasicQos(
                        $header['channel'],
                        Value\LongValue::decode($data),
                        Value\ShortValue::decode($data),
                        Value\BooleanValue::decode($data)
                    );
                }
                if ($method === "\x00\x0B") {
                    return new Method\BasicQosOk(
                        $header['channel']
                    );
                }
                if ($method === "\x00\x14") {
                    return new Method\BasicConsume(
                        $header['channel'],
                        Value\ShortValue::decode($data),
                        Value\ShortStringValue::decode($data),
                        Value\ShortStringValue::decode($data),
                        (bool) ($flags = Value\OctetValue::decode($data)) & 1,
                        (bool) $flags & 2,
                        (bool) $flags & 4,
                        (bool) $flags & 8,
                        Value\TableValue::decode($data)
                    );
                }
                if ($method === "\x00\x15") {
                    return new Method\BasicConsumeOk(
                        $header['channel'],
                        Value\ShortStringValue::decode($data)
                    );
                }
                if ($method === "\x00\x1E") {
                    return new Method\BasicCancel(
                        $header['channel'],
                        Value\ShortStringValue::decode($data),
                        Value\BooleanValue::decode($data)
                    );
                }
                if ($method === "\x00\x1F") {
                    return new Method\BasicCancelOk(
                        $header['channel'],
                        Value\ShortStringValue::decode($data)
                    );
                }
                if ($method === "\x00\x28") {
                    return new Method\BasicPublish(
                        $header['channel'],
                        Value\ShortValue::decode($data),
                        Value\ShortStringValue::decode($data),
                        Value\ShortStringValue::decode($data),
                        (bool) ($flags = Value\OctetValue::decode($data)) & 1,
                        (bool) $flags & 2
                    );
                }
                if ($method === "\x00\x32") {
                    return new Method\BasicReturn(
                        $header['channel'],
                        Value\ShortValue::decode($data),
                        Value\ShortStringValue::decode($data),
                        Value\ShortStringValue::decode($data),
                        Value\ShortStringValue::decode($data)
                    );
                }
                if ($method === "\x00\x3C") {
                    return new Method\BasicDeliver(
                        $header['channel'],
                        Value\ShortStringValue::decode($data),
                        Value\LongLongValue::decode($data),
                        Value\BooleanValue::decode($data),
                        Value\ShortStringValue::decode($data),
                        Value\ShortStringValue::decode($data)
                    );
                }
                if ($method === "\x00\x46") {
                    return new Method\BasicGet(
                        $header['channel'],
                        Value\ShortValue::decode($data),
                        Value\ShortStringValue::decode($data),
                        Value\BooleanValue::decode($data)
                    );
                }
                if ($method === "\x00\x47") {
                    return new Method\BasicGetOk(
                        $header['channel'],
                        Value\LongLongValue::decode($data),
                        Value\BooleanValue::decode($data),
                        Value\ShortStringValue::decode($data),
                        Value\ShortStringValue::decode($data),
                        Value\LongValue::decode($data)
                    );
                }
                if ($method === "\x00\x48") {
                    return new Method\BasicGetEmpty(
                        $header['channel'],
                        Value\ShortStringValue::decode($data)
                    );
                }
                if ($method === "\x00\x50") {
                    return new Method\BasicAck(
                        $header['channel'],
                        Value\LongLongValue::decode($data),
                        Value\BooleanValue::decode($data)
                    );
                }
                if ($method === "\x00\x5A") {
                    return new Method\BasicReject(
                        $header['channel'],
                        Value\LongLongValue::decode($data),
                        Value\BooleanValue::decode($data)
                    );
                }
                if ($method === "\x00\x64") {
                    return new Method\BasicRecoverAsync(
                        $header['channel'],
                        Value\BooleanValue::decode($data)
                    );
                }
                if ($method === "\x00\x6E") {
                    return new Method\BasicRecover(
                        $header['channel'],
                        Value\BooleanValue::decode($data)
                    );
                }
                if ($method === "\x00\x6F") {
                    return new Method\BasicRecoverOk(
                        $header['channel']
                    );
                }
                if ($method === "\x00\x78") {
                    return new Method\BasicNack(
                        $header['channel'],
                        Value\LongLongValue::decode($data),
                        (bool) ($flags = Value\OctetValue::decode($data)) & 1,
                        (bool) $flags & 2
                    );
                }
            }

            if ($class === "\x00\x5A") {
                if ($method === "\x00\x0A") {
                    return new Method\TxSelect(
                        $header['channel']
                    );
                }
                if ($method === "\x00\x0B") {
                    return new Method\TxSelectOk(
                        $header['channel']
                    );
                }
                if ($method === "\x00\x14") {
                    return new Method\TxCommit(
                        $header['channel']
                    );
                }
                if ($method === "\x00\x15") {
                    return new Method\TxCommitOk(
                        $header['channel']
                    );
                }
                if ($method === "\x00\x1E") {
                    return new Method\TxRollback(
                        $header['channel']
                    );
                }
                if ($method === "\x00\x1F") {
                    return new Method\TxRollbackOk(
                        $header['channel']
                    );
                }
            }

            if ($class === "\x00\x55") {
                if ($method === "\x00\x0A") {
                    return new Method\ConfirmSelect(
                        $header['channel'],
                        Value\BooleanValue::decode($data)
                    );
                }
                if ($method === "\x00\x0B") {
                    return new Method\ConfirmSelectOk(
                        $header['channel']
                    );
                }
            }

            throw new \InvalidArgumentException(sprintf(
                'Invalid method received %d:%d',
                Binary::unpackbe('s', $class),
                Binary::unpackbe('s', $method)
            ));
        } elseif ($header['type'] === 2) {
            $parameters = unpack('nclass/nweight/Jsize/nflags', $data->read(14));
            $flags = $parameters['flags'];
            $properties = [];
            if ($flags & 32768) {
                $properties['content-type'] = Value\ShortStringValue::decode($data);
            }

            if ($flags & 16384) {
                $properties['content-encoding'] = Value\ShortStringValue::decode($data);
            }

            if ($flags & 8192) {
                $properties['headers'] = Value\TableValue::decode($data);
            }

            if ($flags & 4096) {
                $properties['delivery-mode'] = Value\OctetValue::decode($data);
            }

            if ($flags & 2048) {
                $properties['priority'] = Value\OctetValue::decode($data);
            }

            if ($flags & 1024) {
                $properties['correlation-id'] = Value\ShortStringValue::decode($data);
            }

            if ($flags & 512) {
                $properties['reply-to'] = Value\ShortStringValue::decode($data);
            }

            if ($flags & 256) {
                $properties['expiration'] = Value\ShortStringValue::decode($data);
            }

            if ($flags & 128) {
                $properties['message-id'] = Value\ShortStringValue::decode($data);
            }

            if ($flags & 64) {
                $properties['timestamp'] = Value\LongLongValue::decode($data);
            }

            if ($flags & 32) {
                $properties['type'] = Value\ShortStringValue::decode($data);
            }

            if ($flags & 16) {
                $properties['user-id'] = Value\ShortStringValue::decode($data);
            }

            if ($flags & 8) {
                $properties['app-id'] = Value\ShortStringValue::decode($data);
            }

            if ($flags & 4) {
                $properties['reserved'] = Value\ShortStringValue::decode($data);
            }

            return new Header(
                $header['channel'],
                $parameters['class'],
                $parameters['weight'],
                $parameters['size'],
                $properties
            );
        } elseif ($header['type'] === 3) {
            return new Content($header['channel'], $data->read($header['size']));
        } elseif ($header['type'] === 8) {
            return new Heartbeat($header['channel']);
        }

        throw new \InvalidArgumentException(sprintf('Invalid frame type %d', $header['type']));
    }
}
