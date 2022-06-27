<?php

namespace AgDevelop\ForkingSupervisor\Watchdog;

interface WatchdogBuilderInterface
{
    public function build(): WatchdogInterface;
}