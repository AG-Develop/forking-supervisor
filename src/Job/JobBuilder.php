<?php

namespace AgDevelop\JobSupervisor\Job;

class JobBuilder implements JobBuilderInterface
{
    private int $jobId = 1;

    public function __construct(
        private string $jobClass,
    ) {

    }

    public function build(): JobInterface
    {
        $job = new $this->jobClass;
        $job
            ->setJobId($this->jobId++);
        return $job;
    }
}