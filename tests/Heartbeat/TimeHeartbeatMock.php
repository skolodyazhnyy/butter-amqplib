<?php

namespace ButterAMQPTest\Heartbeat;

use ButterAMQP\Heartbeat\TimeHeartbeat;

class TimeHeartbeatMock extends TimeHeartbeat
{
    /**
     * @var callable
     */
    private $timeFunction;

    /**
     * @param int      $delay
     * @param callable $timeFunction
     */
    public function __construct($delay, callable $timeFunction)
    {
        parent::__construct($delay);

        $this->timeFunction = $timeFunction;
    }

    /**
     * @return int
     */
    protected function time()
    {
        return call_user_func($this->timeFunction);
    }
}
