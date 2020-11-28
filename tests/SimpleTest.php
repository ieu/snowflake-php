<?php


use Ieu\Snowflake\Snowflake;
use PHPUnit\Framework\TestCase;

class SimpleTest extends TestCase
{
    public function testTimestamp()
    {
        $datacenterId = mt_rand(0, 0x1F);
        $workerId = mt_rand(0, 0x1F);
        $snowflake = new Snowflake($datacenterId, $workerId);
        $id = $snowflake->nextId();
        $this->assertEquals($snowflake->getLastTimestamp(), (($id >> 22) & 0x1FFFFFFFFFF) + Snowflake::EPOCH);
    }

    public function testEarlierTimestamp()
    {
        $datacenterId = mt_rand(0, 0x1F);
        $workerId = mt_rand(0, 0x1F);
        $snowflake = new Snowflake($datacenterId, $workerId);

        $id0 = $snowflake->nextId();
        $timestamp0 = ($id0 >> 22) & 0x1FFFFFFFFFF;

        $refObject = new ReflectionObject($snowflake);
        $refProperty = $refObject->getProperty('lastTimestamp');
        $refProperty->setAccessible(true);
        $refProperty->setValue($snowflake, $timestamp0 - mt_rand(0, 1000));

        $id1 = $snowflake->nextId();
        $timestamp1 = ($id0 >> 22) & 0x1FFFFFFFFFF;

        $this->assertFalse($timestamp1 < $timestamp0, "timestamp($timestamp1) less than previous one($timestamp0)");
    }

    public function testNegativeDatacenterId()
    {
        $this->expectException(\InvalidArgumentException::class);

        $workerId = mt_rand(0, 0x1F);
        $snowflake = new Snowflake(-1, $workerId);
    }

    public function testLargeDatacenterId()
    {
        $this->expectException(\InvalidArgumentException::class);

        $workerId = mt_rand(0, 0x1F);
        $snowflake = new Snowflake(0x20, $workerId);
    }

    public function testNegativeWorkerId()
    {
        $this->expectException(\InvalidArgumentException::class);

        $datacenterId = mt_rand(0, 0x1F);
        $snowflake = new Snowflake($datacenterId, -1);
    }

    public function testLargeWorkerId()
    {
        $this->expectException(\InvalidArgumentException::class);

        $datacenterId = mt_rand(0, 0x1F);
        $snowflake = new Snowflake($datacenterId, 0x20);
    }

    public function testDatacenterId()
    {
        $datacenterId = mt_rand(0, 0x1F);
        $workerId = mt_rand(0, 0x1F);
        $snowflake = new Snowflake($datacenterId, $workerId);

        $id = $snowflake->nextId();
        $this->assertEquals($snowflake->getDatacenterId(), (($id >> 17) & 0x1F));
    }

    public function testWorkerId()
    {
        $datacenterId = mt_rand(0, 0x1F);
        $workerId = mt_rand(0, 0x1F);
        $snowflake = new Snowflake($datacenterId, $workerId);

        $id = $snowflake->nextId();
        $this->assertEquals($snowflake->getWorkerId(), (($id >> 12) & 0x1F));
    }

    public function testSequence()
    {
        $datacenterId = mt_rand(0, 0x1F);
        $workerId = mt_rand(0, 0x1F);
        $snowflake = new Snowflake($datacenterId, $workerId);

        $ids = [];
        for ($i = 0; $i < 0x5000; ++$i) {
            $ids[] = $snowflake->nextId();
        }

        $id0 = $ids[0];
        $prevTimestamp = ($id0 >> 22) & 0x1FFFFFFFFFF;
        $prevSequence = $id0 & 0xFFF;
        for ($i = 1; $i < count($ids); ++$i) {
            $id = $ids[$i];
            $timestamp = ($id >> 22) & 0x1FFFFFFFFFF;
            $sequence = $id & 0xFFF;
            if ($timestamp == $prevTimestamp) {
                $this->assertEquals($prevSequence + 1, $sequence);
            }
            $prevTimestamp = $timestamp;
            $prevSequence = $sequence;
        }
    }

    public function testDuplicateId()
    {
        $datacenterId = mt_rand(0, 0x1F);
        $workerId = mt_rand(0, 0x1F);
        $snowflake = new Snowflake($datacenterId, $workerId);

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
}