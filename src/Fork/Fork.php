<?php

namespace AgDevelop\JobSupervisor\Fork;

use AgDevelop\JobSupervisor\Exception\ForkFailedException;
use AgDevelop\JobSupervisor\Exception\JobException;
use AgDevelop\JobSupervisor\Job\JobInterface;
use AgDevelop\JobSupervisor\Watchdog\WatchdogInterface;

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