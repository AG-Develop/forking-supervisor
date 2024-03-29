<?php

namespace AgDevelop\ForkingSupervisor\Job;

use AgDevelop\ForkingSupervisor\Watchdog\WatchdogInterface;
use Psr\Log\LoggerInterface;

trait JobTrait
{
    private WatchdogInterface $watchdog;

    private LoggerInterface $logger;

    private int $retries = 0;

    private bool $shouldRetryOnFail = true;

    private readonly string $jobId;

    public function setShouldRetryOnFail(bool $shouldRetryOnFail): self
    {
        $this->shouldRetryOnFail = $shouldRetryOnFail;

        return $this;
    }

    public function shouldRetryOnFail(): bool
    {
        return $this->shouldRetryOnFail;
    }

    public function incrementRetries(): void
    {
        ++$this->retries;
    }

    public function setLogger(LoggerInterface $logger): self
    {
        $this->logger = $logger;

        return $this;
    }

    public function getJobId(): string
    {
        return $this->jobId;
    }

    public function setJobId(string $id): self
    {
        $this->jobId = $id;

        return $this;
    }

    public function getWatchdog(): WatchdogInterface
    {
        return $this->watchdog;
    }

    public function setWatchdog(WatchdogInterface $watchdog): self
    {
        $this->watchdog = $watchdog;

        return $this;
    }
}
