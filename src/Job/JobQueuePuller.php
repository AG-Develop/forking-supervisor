<?php

namespace AgDevelop\ForkingSupervisor\Job;

class JobQueuePuller implements JobQueuePullerInterface
{
    private int $jobId = 1;

    public function __construct(
        private string $jobClass,
    ) {

    }

    public function pull(): JobInterface|null
    {
        $job = new $this->jobClass;
        $job
            ->setJobId($this->jobId++);
        return $job;
    }
}