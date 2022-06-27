<?php

namespace AgDevelop\ForkingSupervisor;

use AgDevelop\ForkingSupervisor\Fork\ForkManager;
use Coff\Ticker\CallableTick;
use Coff\Ticker\Ticker;
use Coff\Ticker\Time;
use Psr\Log\LoggerInterface;

class Supervisor
{
    private Ticker $ticker;

    public function __construct(
        private readonly ForkManager $processManager,
        protected readonly ?LoggerInterface $logger = null,
        private readonly int $refillSlotsInterval = 15,
        private readonly int $cleanupInterval = 60,
    ) {
        $this->ticker = new Ticker();

        $this->ticker->addTick(
            new CallableTick(
                Time::SECOND,
                $this->refillSlotsInterval,
                [$this, "replenishSlots"]
            )
        );

        $this->ticker->addTick(
            new CallableTick(
                Time::SECOND,
                $this->cleanupInterval,
                [$this, "cleanup"])
        );
    }

    public function replenishSlots(): void {
        $this->logger->debug('Running replenish');
        $this->processManager->vacateSlots();
        $this->processManager->refillVacatedSlots();
    }

    public function cleanup(): void {
        $this->logger->debug('Running cleanup');
        $this->processManager->cleanSlots();
    }

    public function run()
    {
        $this->ticker->loop();
    }

}