<?php

namespace ButterAMQP;

/**
 * Message returned back to the publisher.
 */
class Returned extends Message
{
    /**
     * @var string
     */
    private $exchange;

    /**
     * @var string
     */
    private $routingKey;

    /**
     * @var int
     */
    private $replyCode;

    /**
     * @var string
     */
    private $replyText;

    /**
     * @param int    $replyCode
     * @param string $replyText
     * @param string $exchange
     * @param string $routingKey
     * @param string $body
     * @param array  $properties
     */
    public function __construct(
        $replyCode,
        $replyText,
        $exchange,
        $routingKey,
        $body,
        array $properties
    ) {
        $this->replyCode = $replyCode;
        $this->replyText = $replyText;
        $this->exchange = $exchange;
        $this->routingKey = $routingKey;

        parent::__construct($body, $properties);
    }

    /**
     * Exchange where message was sent initially.
     *
     * @return string
     */
    public function getExchange()
    {
        return $this->exchange;
    }

    /**
     * Routing key.
     *
     * @return string
     */
    public function getRoutingKey()
    {
        return $this->routingKey;
    }

    /**
     * Define how to print object when dumping.
     *
     * @return array
     */
    public function __debugInfo()
    {
        return array_merge(parent::__debugInfo(), [
            'reply_code' => $this->replyCode,
            'reply_text' => $this->replyText,
            'exchange' => $this->exchange,
            'routing_key' => $this->routingKey,
        ]);
    }
}
