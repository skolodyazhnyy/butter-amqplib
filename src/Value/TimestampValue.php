<?php

namespace AMQPLib\Value;

use AMQPLib\Binary;
use AMQPLib\Buffer;

class TimestampValue extends AbstractValue
{
    /**
     * @var \DateTime
     */
    private $dateTime;

    /**
     * @param \DateTime $dateTime
     */
    public function __construct(\DateTime $dateTime)
    {
        $this->dateTime = $dateTime;
    }

    /**
     * @return \DateTime
     */
    public function getDateTime()
    {
        return $this->dateTime;
    }

    /**
     * @param int $value
     *
     * @return string
     */
    public static function encode($value)
    {
        return Binary::packbe('q', $value);
    }

    /**
     * @param Buffer $data
     *
     * @return string
     */
    public static function decode(Buffer $data)
    {
        return Binary::unpackbe('q', $data->read(8));
    }

    /**
     * @return string
     */
    public function serialize()
    {
        return self::encode($this->dateTime->getTimestamp());
    }

    /**
     * @param Buffer $data
     *
     * @return $this
     */
    public static function unserialize(Buffer $data)
    {
        return new self(\DateTime::createFromFormat('U', self::decode($data)));
    }
}
