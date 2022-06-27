<?php

namespace AgDevelop\JobSupervisor\Watchdog;

interface WatchdogBuilderInterface
{
    public function build(): WatchdogInterface;
}