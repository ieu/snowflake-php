<?php


namespace Ieu\Snowflake;


use InvalidArgumentException;

class Snowflake
{
    /** @var int */
    const EPOCH = 1288834974657;

    /** @var int */
    const SEQUENCE_BITS = 12;

    /** @var int */
    const WORKER_ID_BITS = 5;

    /** @var int */
    const DATACENTER_ID_BITS = 5;

    /** @var int */
    const TIMESTAMP_BITS = 41;

    /** @var int */
    const WORKER_ID_SHIFT = self::SEQUENCE_BITS;

    /** @var int */
    const DATACENTER_ID_SHIFT = self::SEQUENCE_BITS + self::WORKER_ID_BITS;

    /** @var int */
    const TIMESTAMP_SHIFT = self::SEQUENCE_BITS + self::WORKER_ID_BITS + self::DATACENTER_ID_BITS;

    /** @var int */
    const MAX_WORKER_ID = -1 ^ (-1 << self::WORKER_ID_BITS);

    /** @var int */
    const MAX_DATACENTER_ID = -1 ^ (-1 << self::DATACENTER_ID_BITS);

    /** @var int */
    const SEQUENCE_MASK = -1 ^ (-1 << self::SEQUENCE_BITS);

    /** @var int */
    const WORKER_ID_MASK = -1 ^ (-1 << self::WORKER_ID_BITS);

    /** @var int */
    const DATACENTER_ID_MASK = -1 ^ (-1 << self::DATACENTER_ID_BITS);

    /** @var int */
    const TIMESTAMP_MASK = -1 ^ (-1 << self::TIMESTAMP_BITS);

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
        if ($workerId < 0 || $workerId > static::MAX_WORKER_ID) {
            throw new InvalidArgumentException("Invalid Worker ID");
        }

        if ($datacenterId < 0 || $datacenterId > static::MAX_DATACENTER_ID) {
            throw new InvalidArgumentException("Invalid Datacenter ID");
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
        } while($now < $this->lastTimestamp || $this->nextSequence >= 1 << static::SEQUENCE_BITS);

        if ($now > $this->lastTimestamp) {
            $sequence = $this->nextSequence = 0;
        } else {
            $sequence = ++$this->nextSequence;
        }

        $this->lastTimestamp = $now;

        $datacenterId = $this->datacenterId;
        $workerId = $this->workerId;

        return ( ($now - static::EPOCH) << static::TIMESTAMP_SHIFT )
             | ( $datacenterId << static::DATACENTER_ID_SHIFT )
             | ( $workerId << static::WORKER_ID_SHIFT )
             | $sequence
             ;
    }
}
