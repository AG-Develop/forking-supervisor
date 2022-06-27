<?php

namespace AgDevelop\JobSupervisor\Watchdog;

use DateTime;

interface WatchdogInterface
{
    public function setLastOccupied(DateTime|string $time = null): self;

    public function shouldExit(): bool;

    public function shouldBeTerminated(): bool;
}