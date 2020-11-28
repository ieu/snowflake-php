<?php


use Ieu\Snowflake\Snowflake;
use PHPUnit\Framework\TestCase;

class SimpleTest extends TestCase
{
    public function testTimestamp()
    {
        $snowflake = $this->createSnowflake();
        $id = $snowflake->nextId();
        $this->assertEquals($snowflake->getLastTimestamp(), $this->extractUnixTimestamp($id, $snowflake));
    }

    public function testEarlierTimestamp()
    {
        $snowflake = $this->createSnowflake();

        $id0 = $snowflake->nextId();
        $timestamp0 = $this->extractUnixTimestamp($id0, $snowflake);

        $refObject = new ReflectionObject($snowflake);
        $refProperty = $refObject->getProperty('lastTimestamp');
        $refProperty->setAccessible(true);
        $refProperty->setValue($snowflake, $timestamp0 - mt_rand(0, 1000));

        $id1 = $snowflake->nextId();
        $timestamp1 = $this->extractUnixTimestamp($id1, $snowflake);

        $this->assertFalse($timestamp1 < $timestamp0, "timestamp($timestamp1) less than previous one($timestamp0)");
    }

    public function testNegativeDatacenterId()
    {
        $this->expectException(\InvalidArgumentException::class);

        $workerId = mt_rand(0, Snowflake::MAX_WORKER_ID);
        $snowflake = new Snowflake(-1, $workerId);
    }

    public function testLargeDatacenterId()
    {
        $this->expectException(\InvalidArgumentException::class);

        $workerId = mt_rand(0, Snowflake::MAX_WORKER_ID);
        $snowflake = new Snowflake(Snowflake::MAX_WORKER_ID + 1, $workerId);
    }

    public function testNegativeWorkerId()
    {
        $this->expectException(\InvalidArgumentException::class);

        $datacenterId = mt_rand(0, Snowflake::MAX_DATACENTER_ID);
        $snowflake = new Snowflake($datacenterId, -1);
    }

    public function testLargeWorkerId()
    {
        $this->expectException(\InvalidArgumentException::class);

        $datacenterId = mt_rand(0, Snowflake::MAX_DATACENTER_ID);
        $snowflake = new Snowflake($datacenterId, Snowflake::MAX_DATACENTER_ID + 1);
    }

    public function testDatacenterId()
    {
        $snowflake = $this->createSnowflake();

        $id = $snowflake->nextId();
        $this->assertEquals($snowflake->getDatacenterId(), $this->extractDatacenterId($id, $snowflake));
    }

    public function testWorkerId()
    {
        $snowflake = $this->createSnowflake();

        $id = $snowflake->nextId();
        $this->assertEquals($snowflake->getWorkerId(), $this->extractWorkerId($id, $snowflake));
    }

    public function testSequence()
    {
        $snowflake = $this->createSnowflake();

        $ids = [];
        for ($i = 0; $i < 0x5000; ++$i) {
            $ids[] = $snowflake->nextId();
        }

        $id0 = $ids[0];
        $prevTimestamp = $this->extractSequence($id0, $snowflake);
        $prevSequence = $id0;
        for ($i = 1; $i < count($ids); ++$i) {
            $id = $ids[$i];
            $timestamp = $this->extractTimestamp($id, $snowflake);
            $sequence = $id;
            if ($timestamp == $prevTimestamp) {
                $this->assertEquals($prevSequence + 1, $sequence);
            }
            $prevTimestamp = $timestamp;
            $prevSequence = $sequence;
        }
    }

    public function testDuplicateId()
    {
        $snowflake = $this->createSnowflake();

        $ids = [];

        for ($i = 0; $i <= 0x5000; ++$i) {
            $ids[] = $snowflake->nextId();
        }

        $this->assertArrayDuplicate($ids);
    }

    /**
     * Asserts that an array has duplicated value.
     *
     * @param array|ArrayAccess $array
     * @param string            $message
     */
    public function assertArrayDuplicate($array, $message = '')
    {
        if (!(is_array($array) || $array instanceof ArrayAccess)) {
            throw PHPUnit_Util_InvalidArgumentHelper::factory(
                2,
                'array or ArrayAccess'
            );
        }

        $constraint = new DuplicateConstraint();

        static::assertThat($array, $constraint, $message);
    }

    public function createSnowflake()
    {
        return new Snowflake(
            mt_rand(0, Snowflake::MAX_DATACENTER_ID),
            mt_rand(0, Snowflake::MAX_WORKER_ID)
        );
    }

    /**
     * @param int       $id
     * @param Snowflake $snowflake
     */
    public function extractSequence($id, $snowflake) {
        return $id & $snowflake::SEQUENCE_MASK;
    }

    /**
     * @param int       $id
     * @param Snowflake $snowflake
     */
    public function extractWorkerId($id, $snowflake) {
        return ($id >> $snowflake::WORKER_ID_SHIFT) & $snowflake::WORKER_ID_MASK;
    }

    /**
     * @param int       $id
     * @param Snowflake $snowflake
     */
    public function extractDatacenterId($id, $snowflake) {
        return ($id >> $snowflake::DATACENTER_ID_SHIFT) & $snowflake::DATACENTER_ID_MASK;
    }

    /**
     * @param int       $id
     * @param Snowflake $snowflake
     */
    public function extractTimestamp($id, $snowflake) {
        return ($id >> $snowflake::TIMESTAMP_SHIFT) & $snowflake::TIMESTAMP_MASK;
    }

    /**
     * @param int       $id
     * @param Snowflake $snowflake
     */
    public function extractUnixTimestamp($id, $snowflake) {
        return $this->extractTimestamp($id, $snowflake) + $snowflake::EPOCH;
    }
}