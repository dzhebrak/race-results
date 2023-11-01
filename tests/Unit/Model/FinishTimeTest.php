<?php

namespace App\Tests\Unit\Model;

use App\Model\FinishTime;
use PHPUnit\Framework\TestCase;

class FinishTimeTest extends TestCase
{

    /**
     * @dataProvider toTimeDataprovider
     */
    public function testToString(int $seconds, string $time)
    {
        $this->assertSame($time, (new FinishTime($seconds))->toString());
    }

    /**
     * @dataProvider toSecondsDataprovider
     */
    public function testToSeconds(string $time, int $seconds)
    {
        $this->assertSame($seconds, (FinishTime::fromTime($time))->toSeconds());
    }

    public function testToSecondsException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('"0:0" is not a valid finish time value. Finish time must be specified in the format "h:mm:ss", e.g. "4:07:45"');

        FinishTime::fromTime('0:0');
    }

    public function toTimeDataprovider()
    {
        return [
            [0, '0:00:00'],
            [1, '0:00:01'],
            [633, '0:10:33'],
            [8025, '2:13:45'],
            [21852, '6:04:12'],
            [85907, '23:51:47'],
            [86400, '24:00:00'],
            [86401, '24:00:01'],
        ];
    }

    public function toSecondsDataprovider()
    {
        return [
            ['0:00:00', 0],
            ['0:00:01', 1],
            ['0:10:33', 633],
            ['2:13:45', 8025],
            ['6:04:12', 21852],
            ['23:51:47', 85907],
            ['24:00:00', 86400],
            ['24:00:01', 86401]
        ];
    }
}
