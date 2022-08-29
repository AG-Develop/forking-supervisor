<?php

namespace AgDevelop\ForkingSupervisor\Pcntl;

class PcntlWaitResult
{
    public function __construct(
        private int $pid,
        private PcntlEvent $pcntlEvent,
        private bool $hasFinished,
        private ?int $returnCode,
        private ?int $termSignal,
    ) {
    }

    public function getPid(): int
    {
        return $this->pid;
    }

    public function getPcntlEvent(): PcntlEvent
    {
        return $this->pcntlEvent;
    }

    public function getReturnCode(): int
    {
        return $this->returnCode;
    }

    public function getTermSignal(): int
    {
        return $this->termSignal;
    }

    public function hasFinished(): bool
    {
        return $this->hasFinished;
    }
}
