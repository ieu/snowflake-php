<?php


use Ieu\Snowflake\Snowflake;
use PHPUnit\Framework\TestCase;

class SimpleTest extends TestCase
{
    public function testTimestamp()
    {
        $snowflake = new Snowflake(1);
        $id = $snowflake->nextId();
        $this->assertEquals($snowflake->getLastTimestamp(), (($id >> 22) & 0x1FFFFFFFFFF) + Snowflake::EPOCH);
    }

    public function testEarlierTimestamp()
    {
        $snowflake = new Snowflake(1);
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

    public function testNegativeWorkerId()
    {
        $this->expectException(\InvalidArgumentException::class);

        new Snowflake(-1);
    }

    public function testLargeWorkerId()
    {
        $this->expectException(\InvalidArgumentException::class);

        new Snowflake(0x400);
    }

    public function testWorkId()
    {
        $workerId = mt_rand(0, 0x3FF);
        $snowflake = new Snowflake($workerId);
        $id = $snowflake->nextId();
        $this->assertEquals($snowflake->getWorkerId(), ($id >> 12) & 0x3FF);
    }

    public function testSequence()
    {
        $snowflake = new Snowflake(1);

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
        $snowflake = new Snowflake(1);

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