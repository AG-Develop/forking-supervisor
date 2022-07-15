<?php

namespace AgDevelop\ForkingSupervisor\Watchdog;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Exception;

class Watchdog implements WatchdogInterface
{
    protected DateTimeInterface $lastOccupied;

    private readonly DateTimeInterface $createdAt;

    public function __construct(
        private ?int $maxUnoccupiedTime,
        private ?int $maxAliveTime,
    ) {
        $this->createdAt = new DateTimeImmutable('now');
        $this->lastOccupied = clone $this->createdAt;
    }

    /**
     * @throws Exception
     */
    public function setLastOccupied(DateTime|string $time = null): self
    {
        if (!$time instanceof DateTime) {
            if (null === $time) {
                $time = new DateTimeImmutable('now');
            } else {
                $time = new DateTimeImmutable($time);
            }
        }

        $this->lastOccupied = $time;

        return $this;
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

    private function hasReachedMaxUnoccupiedTime(): bool
    {
        if (null === $this->maxUnoccupiedTime) {
            return false;
        }

        return $this->lastOccupied->getTimestamp() + $this->maxUnoccupiedTime < time();
    }

    private function hasReachedMaxAliveTime(): bool
    {
        if (null === $this->maxAliveTime) {
            return false;
        }

        return $this->createdAt->getTimestamp() + $this->maxAliveTime < time();
    }

    public function shouldBeTerminated(): bool
    {
        if ($this->hasReachedMaxAliveTime()) {
            return true;
        }

        return false;
    }
}
