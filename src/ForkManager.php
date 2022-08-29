<?php

namespace AgDevelop\ForkingSupervisor;

use AgDevelop\ForkingSupervisor\Exception\ForkFailedException;
use AgDevelop\ForkingSupervisor\Exception\ForkNotFoundException;
use AgDevelop\ForkingSupervisor\Exception\PcntlException;
use AgDevelop\ForkingSupervisor\Exception\UnknownReturnCodeException;
use AgDevelop\ForkingSupervisor\Exception\UnknownSignalException;
use AgDevelop\ForkingSupervisor\Job\JobInterface;
use AgDevelop\ForkingSupervisor\Job\JobQueuePullerInterface;
use AgDevelop\ForkingSupervisor\Pcntl\PcntlEvent;
use AgDevelop\ForkingSupervisor\Pcntl\PcntlProvider;
use AgDevelop\ForkingSupervisor\Watchdog\WatchdogBuilderInterface;
use Psr\Log\LoggerInterface;

class ForkManager
{
    /** @var array<int,Fork> */
    protected array $children = [];

    public function __construct(
        private int $slots,
        private WatchdogBuilderInterface $watchdogBuilder,
        private ForkBuilderInterface $forkBuilder,
        private JobQueuePullerInterface $jobQueue,
        private PcntlProvider $pcntlProvider,
        private ?LoggerInterface $logger = null,
    ) {
    }

    /**
     * this method will call itself recurrently until it finds no children exited.
     */
    public function vacateSlots(): void
    {
        if (0 == $this->count()) {
            return;
        }

        try {
            $pcntlResult = $this->pcntlProvider->wait(WNOHANG | WUNTRACED);

            if (null === $pcntlResult) {
                // no child exited; this condition breaks recurrence cycle
                return;
            }

            if ($pcntlResult->hasFinished()) {
                $pid = $pcntlResult->getPid();
                $fork = $this->get($pid);
                $job = $fork->getJob();
                $event = $pcntlResult->getPcntlEvent();

                $this->unlink($pid);

                $failed = PcntlEvent::SIGNALED || (PcntlEvent::EXITED == $event && $pcntlResult->getReturnCode() > 0);

                if ($failed && $job->shouldRetryOnFail()) {
                    $this->logger?->info(sprintf('Job failure. Relaunching failed job %s under fresh fork', $job->getJobId()));
                    $job->incrementRetries();
                    $this->refillSlotWith($job);
                }
            }

            // recurrently check if any other slots are to be vacated
            $this->vacateSlots();
        } catch (ForkNotFoundException $e) {
            $this->logger?->info('Old fork process is no longer monitored - ignoring.');
        } catch (UnknownSignalException $e) {
            $this->logger?->error('System reported fork to be signaled to terminate but failed to determine signal');
        } catch (UnknownReturnCodeException $e) {
            $this->logger?->error('System reported fork exited but failed to determine return code');
        } catch (PcntlException $e) {
            $this->logger?->error($e->getMessage());
        }
    }

    protected function count(): int
    {
        return count($this->children);
    }

    /**
     * @throws ForkNotFoundException
     */
    protected function get(int $pid): Fork
    {
        if (!isset($this->children[$pid])) {
            throw new ForkNotFoundException('Unknown fork');
        }

        $thread = $this->children[$pid];

        return $thread;
    }

    protected function unlink(int $pid): void
    {
        unset($this->children[$pid]);
    }

    /**
     * @throws ForkFailedException
     */
    public function refillSlotWith(JobInterface $job): void
    {
        $this->logger?->info(sprintf('Spawning new fork for job %s', $job->getJobId()));
        $child = $this->forkBuilder->build($job, $this->watchdogBuilder->build());
        $this->add($child);
    }

    private function add(Fork $child): void
    {
        $this->children[$child->getPid()] = $child;
    }

    public function refillVacatedSlots(): void
    {
        while ($this->count() < $this->slots) {
            $job = $this->jobQueue->pull();

            if (null === $job) {
                $this->logger?->info('JobQueue returned no job. Skipping further refill');

                return;
            }

            $this->refillSlotWith($job);
        }
    }

    public function cleanSlots(): void
    {
        $now = time();

        foreach ($this->children as $fork) {
            if ($fork->getWatchdog()->shouldBeTerminated()) {
                $job = $fork->getJob();
                $this->logger?->info(
                    sprintf('Process for job %s exceeded its max allowed age. Sending kill signal.', $job->getJobId())
                );
                $this->kill($fork);
                $this->refillSlotWith($job);
            }
        }
    }

    protected function kill(Fork $child): void
    {
        posix_kill($child->getPid(), SIGKILL);
        unset($this->children[$child->getPid()]);
    }
}
