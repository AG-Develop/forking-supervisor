<?php

namespace AgDevelop\JobSupervisor\Fork;

use AgDevelop\JobSupervisor\Exception\ForkFailedException;
use AgDevelop\JobSupervisor\Job\JobInterface;
use AgDevelop\JobSupervisor\Watchdog\Watchdog;

interface ForkBuilderInterface
{
    /**
     * @throws ForkFailedException
     */
    public function build(JobInterface $job, Watchdog $watchdog): Fork;
}