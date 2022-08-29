<?php

namespace AgDevelop\ForkingSupervisor\Test;

use AgDevelop\ForkingSupervisor\Watchdog\Watchdog;
use AgDevelop\ForkingSupervisor\Watchdog\WatchdogBuilder;
use PHPUnit\Framework\TestCase;

class SampleWatchdog extends Watchdog
{
}

class WatchdogBuilderTest extends TestCase
{
    public function testBuild(): void
    {
        $builder = new WatchdogBuilder(10, 10, SampleWatchdog::class);

        $actual = $builder->build();

        $this->assertInstanceOf(SampleWatchdog::class, $actual);
    }
}
