<?php

namespace AgDevelop\ForkingSupervisor\Watchdog;

class Watchdog implements WatchdogInterface
{
    private readonly \DateTimeInterface $createdAt;
    protected \DateTimeInterface $lastOccupied;

    public function __construct(
        private ?int $maxUnoccupiedTime,
        private ?int $maxAliveTime,
    ) {
        $this->createdAt = new \DateTimeImmutable('now');
        $this->lastOccupied = clone $this->createdAt;
    }

    /**
     * @throws \Exception
     */
    public function setLastOccupied(\DateTime|string $time = null): self
    {
        if (!$time instanceof \DateTime) {
            if ($time === null) {
                $time = new \DateTimeImmutable('now');
            } else {
                $time = new \DateTimeImmutable($time);
            }
        }

        $this->lastOccupied = $time;

        return $this;
    }

    private function hasReachedMaxUnoccupiedTime(): bool
    {
        if ($this->maxUnoccupiedTime === null) {
            return false;
        }

        return $this->lastOccupied->getTimestamp() + $this->maxUnoccupiedTime < time();
    }

    private function hasReachedMaxAliveTime(): bool
    {
        if ($this->maxAliveTime === null) {
            return false;
        }

        return $this->createdAt->getTimestamp() + $this->maxAliveTime < time();
    }

    public function shouldExit(): bool
    {
        if ($this->hasReachedMaxUnoccupiedTime()) {
            return true;
        }

        if ($this->hasReachedMaxAliveTime()) {
            return true;
        }

        return false;
    }

    public function shouldBeTerminated(): bool
    {
        if ($this->hasReachedMaxAliveTime()) {
            return true;
        }

        return false;
    }
}