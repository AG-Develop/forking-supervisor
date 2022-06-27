<?php

namespace AgDevelop\ForkingSupervisor\Watchdog;

use AgDevelop\ForkingSupervisor\Exception\WatchdogException;

class WatchdogBuilder implements WatchdogBuilderInterface
{
    public function __construct(
        private readonly int $maxUnoccupiedTime = 5 * 60,
        private readonly int $maxAliveTime = 60 * 60,
        private readonly string $watchDogClass = Watchdog::class,
    ) {
    }

    public function build(): WatchdogInterface
    {
        return new $this->watchDogClass(
            $this->maxUnoccupiedTime,
            $this->maxAliveTime,
        );
    }
}