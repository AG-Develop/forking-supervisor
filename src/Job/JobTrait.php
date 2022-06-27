<?php

namespace AgDevelop\JobSupervisor\Job;

use AgDevelop\JobSupervisor\Watchdog\Watchdog;
use Psr\Log\LoggerInterface;

trait JobTrait
{
    private Watchdog $watchdog;
    private LoggerInterface $logger;
    private int $retries=0;
    private bool $shouldRetryOnFail = true;
    private readonly string $jobId;

    public function setShouldRetryOnFail(bool $shouldRetryOnFail): self {
        $this->shouldRetryOnFail = $shouldRetryOnFail;
        return $this;
    }

    public function shouldRetryOnFail(): bool
    {
        return $this->shouldRetryOnFail;
    }

    public function incrementRetries(): void {
        $this->retries++;
    }

    public function setJobId(string $id): self
    {
        $this->jobId = $id;

        return $this;
    }

    public function setLogger(LoggerInterface $logger): self
    {
        $this->logger = $logger;

        return $this;
    }

    public function setWatchdog(Watchdog $watchdog): self
    {
        $this->watchdog = $watchdog;
        return $this;
    }

    public function getJobId(): string
    {
        return $this->jobId;
    }

    public function getWatchdog(): Watchdog
    {
        return $this->watchdog;
    }
}