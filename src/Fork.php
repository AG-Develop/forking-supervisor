<?php

namespace AgDevelop\ForkingSupervisor;

use AgDevelop\ForkingSupervisor\Job\JobInterface;
use AgDevelop\ForkingSupervisor\Watchdog\WatchdogInterface;

class Fork
{
    public function __construct(
        private int $processId,
        private JobInterface $job,
        private WatchdogInterface $watchdog,
    ) {
    }

    public function getJob(): JobInterface
    {
        return $this->job;
    }

    public function getPid(): int
    {
        return $this->processId;
    }

    public function getWatchdog(): WatchdogInterface
    {
        return $this->watchdog;
    }
}
