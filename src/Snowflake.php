<?php


namespace Ieu\Snowflake;


use InvalidArgumentException;

class Snowflake
{
    /** @var int */
    const EPOCH = 1288834974657;

    /** @var int */
    private $lastTimestamp;

    /** @var int */
    private $workerId;

    /** @var int */
    private $datacenterId;

    /** @var int */
    private $nextSequence;

    /**
     * @param int $workerId
     * @param int $datacenterId
     * @throws InvalidArgumentException
     */
    public function __construct($workerId, $datacenterId)
    {
        if ($workerId < 0 || $workerId > 0x1F) {
            throw new InvalidArgumentException("Invalid Worker ID");
        }

        if ($datacenterId < 0 || $datacenterId > 0x1F) {
            throw new InvalidArgumentException("Invalid Worker ID");
        }

        $this->lastTimestamp = -1;
        $this->workerId = $workerId;
        $this->datacenterId = $datacenterId;
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
    public function getDatacenterId()
    {
        return $this->datacenterId;
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

        $datacenterId = $this->datacenterId;
        $workerId = $this->workerId;

        return ( ( ($now - static::EPOCH) & 0x1FFFFFFFFFF ) << 22 )
             | ( ( $datacenterId & 0x1F ) << 17 )
             | ( ( $workerId & 0x1F ) << 12 )
             | ( $sequence & 0xFFF )
             ;
    }
}
