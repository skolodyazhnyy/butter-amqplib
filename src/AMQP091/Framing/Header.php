<?php

namespace ButterAMQP\AMQP091\Framing;

/**
 * @codeCoverageIgnore
 */
class Header extends Frame
{
    /**
     * @var int
     */
    private $classId;

    /**
     * @var int
     */
    private $weight;

    /**
     * @var int
     */
    private $size;

    /**
     * @var array
     */
    private $properties = [];

    /**
     * @param int   $channel
     * @param int   $classId
     * @param int   $weight
     * @param int   $size
     * @param array $properties
     */
    public function __construct($channel, $classId, $weight, $size, array $properties = [])
    {
        $this->classId = $classId;
        $this->weight = $weight;
        $this->size = $size;
        $this->properties = $properties;

        parent::__construct($channel);
    }

    /**
     * @return int
     */
    public function getClassId()
    {
        return $this->classId;
    }

    /**
     * @return int
     */
    public function getWeight()
    {
        return $this->weight;
    }

    /**
     * @return int
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * @return array
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * @return string
     */
    public function encode()
    {
        $data = pack('nnJ', $this->classId, $this->weight, $this->size).
            Properties::encode($this->properties);

        return "\x02".pack('nN', $this->channel, strlen($data)).$data."\xCE";
    }
}
