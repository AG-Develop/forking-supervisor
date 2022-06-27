<?php

namespace AgDevelop\ForkingSupervisor\Fork;

use AgDevelop\ForkingSupervisor\Exception\ForkFailedException;
use AgDevelop\ForkingSupervisor\Job\JobInterface;
use AgDevelop\ForkingSupervisor\Watchdog\Watchdog;

interface ForkBuilderInterface
{
    /**
     * @throws ForkFailedException
     */
    public function build(JobInterface $job, Watchdog $watchdog): Fork;
}