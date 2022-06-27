<?php

namespace AgDevelop\JobSupervisor\Job;

use AgDevelop\JobSupervisor\Exception\JobException;
use AgDevelop\JobSupervisor\Fork\Fork;
use AgDevelop\JobSupervisor\Watchdog\Watchdog;
use Psr\Log\LoggerInterface;

interface JobInterface
{
    /**
     * @throws JobException
     */
    public function interrupt(): void;

    /**
     * @throws JobException
     */
    public function run(): void;

    public function setWatchdog(Watchdog $watchdog): self;

    public function setShouldRetryOnFail(bool $shouldRetryOnFail): self;

    public function setJobId(string $id): self;

    public function setLogger(LoggerInterface $logger): self;

    public function shouldRetryOnFail(): bool;

    public function incrementRetries(): void;

    public function getJobId(): string;

    public function getWatchdog(): Watchdog;
}