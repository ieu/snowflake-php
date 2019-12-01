<?php


namespace Ieu\Snowflake;


use InvalidArgumentException;

class Snowflake
{
    /** @var int */
    private $lastTimestamp;

    /** @var int */
    private $workerId;

    /** @var int */
    private $nextSequence;

    /**
     * @param int $workerId
     * @throws InvalidArgumentException
     */
    public function __construct($workerId)
    {
        if ($workerId < 0 || $workerId > 0x3FF) {
            throw new InvalidArgumentException("Invalid Worker ID");
        }

        $this->lastTimestamp = -1;
        $this->workerId = $workerId;
        $this->nextSequence = 0;
    }

    /**
     * @return int
     */
    public function getLastTimestamp()
    {
        return $this->lastTimestamp;
    }

    /**
     * @return int
     */
    public function getWorkerId()
    {
        return $this->workerId;
    }

    /**
     * @return int
     */
    public function getNextSequence()
    {
        return $this->nextSequence;
    }

    /**
     * @return int
     */
    public function nextId()
    {
        do {
            $now = (int)(microtime(true) * 1000);
        } while($now < $this->lastTimestamp || $this->nextSequence >= 1 << 12);

        if ($now > $this->lastTimestamp) {
            $sequence = $this->nextSequence = 0;
        } else {
            $sequence = ++$this->nextSequence;
        }

        $this->lastTimestamp = $now;

        $workerId = $this->workerId;

        return ( ( $now & 0x1FFFFFFFFFF ) << 22 ) | ( ( $workerId & 0x3FF ) << 12 ) | ( $sequence & 0xFFF );
    }
}
