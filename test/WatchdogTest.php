<?php

namespace AgDevelop\ForkingSupervisor\Test;

use AgDevelop\ForkingSupervisor\Watchdog\Watchdog;
use PHPUnit\Framework\TestCase;

class WatchdogTest extends TestCase
{
    public function dataProvider()
    {
        return [
            [new \DateTime('-11 seconds'), new \DateTime('-4 seconds'), 10, 5, true, true],
            [new \DateTime('-9 seconds'), new \DateTime('-4 seconds'), 10, 5, false, false],
            [new \DateTime('-11 seconds'), new \DateTime('-6 seconds'), 10, 5, true, true],
            [new \DateTime('-9 seconds'), new \DateTime('-6 seconds'), 10, 5, false, true],
        ];
    }

    /** @dataProvider dataProvider */
    public function testShould(\DateTimeInterface $createdAt, \DateTimeInterface $lastOccupied,
        int $maxAlive, int $maxUnocuppied, bool $shouldBeTerminated, bool $shouldExit): void
    {
        $watchdog = new Watchdog(
            maxUnoccupiedTime: $maxUnocuppied,
            maxAliveTime: $maxAlive,
            createdAt: $createdAt,
        );

        $watchdog->setLastOccupied($lastOccupied);

        $this->assertEquals($shouldBeTerminated, $watchdog->shouldBeTerminated());

        $this->assertEquals($shouldExit, $watchdog->shouldExit());
    }
}
