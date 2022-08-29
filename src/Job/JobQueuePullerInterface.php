<?php

namespace AgDevelop\ForkingSupervisor\Job;

interface JobQueuePullerInterface
{
    public function pull(): ?JobInterface;
}
