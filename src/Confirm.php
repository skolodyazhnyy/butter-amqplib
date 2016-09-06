<?php

namespace ButterAMQP;

class Confirm
{
    /**
     * @var bool
     */
    private $ok;

    /**
     * @var int
     */
    private $deliveryTag;

    /**
     * @var bool
     */
    private $multiple = false;

    /**
     * @param bool $ok
     * @param int  $deliveryTag
     * @param bool $multiple
     */
    public function __construct($ok, $deliveryTag, $multiple)
    {
        $this->ok = $ok;
        $this->deliveryTag = $deliveryTag;
        $this->multiple = $multiple;
    }

    /**
     * @return bool
     */
    public function isOk()
    {
        return $this->ok;
    }

    /**
     * @return int
     */
    public function getDeliveryTag()
    {
        return $this->deliveryTag;
    }

    /**
     * @return bool
     */
    public function isMultiple()
    {
        return $this->multiple;
    }
}
