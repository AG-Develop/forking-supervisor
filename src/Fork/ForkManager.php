<?php

namespace AgDevelop\ForkingSupervisor\Fork;

use AgDevelop\ForkingSupervisor\Exception\ForkFailedException;
use AgDevelop\ForkingSupervisor\Exception\ForkNotFoundException;
use AgDevelop\ForkingSupervisor\Job\JobInterface;
use AgDevelop\ForkingSupervisor\Job\JobBuilderInterface;
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
        private JobBuilderInterface $jobBuilder,
        private ?LoggerInterface $logger = null,
    ) {

    }

    public function vacateSlots(): void
    {

        if ($this->count() == 0) {
            return;
        }

        $status = null;
        $pid = pcntl_wait($status, WNOHANG | WUNTRACED);
        switch ($pid) {
            case -1:
                // error, to be reported
                $this->logger?->error('pcntl_wait() returned -1');
                break;
            case 0:
                // no child exited
                break;
            default:
                // child's status changed
                try {
                    $fork = $this->get($pid);
                    $job = $fork->getJob();
                    $failed = false;
                    $finished = false;

                    switch (true) {
                        case pcntl_wifexited($status):
                            $message = sprintf('Process for job %s exited with status %d', $job->getJobId(),
                                pcntl_wexitstatus($status));
                            $failed = pcntl_wexitstatus($status) !== 0;
                            $finished = true;
                            break;
                        case pcntl_wifsignaled($status):
                            $message = sprintf('Process for job %s finished due to signal %d', $job->getJobId(),
                                pcntl_wtermsig($status));
                            $failed = true;
                            $finished = true;
                            break;
                        case pcntl_wifstopped($status):
                            $message = sprintf('Process for job %s stopped after signal %d', $job->getJobId(),
                                pcntl_wstopsig($status));
                            $failed = false;
                            $finished = false;
                            break;
                        case pcntl_wifcontinued($status):
                            $message = sprintf('Process for job %s continues', $job->getJobId());
                            $failed = false;
                            $finished = false;
                            break;
                    }

                    if ($finished) {
                        $this->unlink($pid);
                    }

                    $this->logger?->info($message);

                    if ($failed && $job->shouldRetryOnFail()) {
                        $this->logger?->info(sprintf('Relaunching failed job %s under fresh fork', $job->getJobId()));
                        $job->incrementRetries();
                        $this->refillSlotWith($job);
                    }

                } catch (ForkNotFoundException $e) {
                    $this->logger?->info('Old fork process is no longer monitored - ignoring.');
                }

                // check if any other slots are to be vacated
                $this->vacateSlots();
        }
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

    public function refillVacatedSlots(): void
    {
        while ($this->count() < $this->slots) {
            $job = $this->jobBuilder->build();
            $this->refillSlotWith($job);
        }
    }

    public function cleanSlots(): void
    {
        $now = time();

        foreach ($this->children as $fork) {
            $job = $fork->getJob();

            if ($fork->getWatchdog()->shouldBeTerminated()) {
                $this->logger?->info(sprintf('Process for job %s exceeded its max allowed age. Sending kill signal.', $job->getJobId()));
                $this->kill($fork);
                $this->refillSlotWith($job);
            }
        }
    }

    private function kill(Fork $child): void {
        posix_kill($child->getPid(), SIGKILL);
        unset($this->children[$child->getPid()]);
    }

    private function add(Fork $child): void {
        $this->children[$child->getPid()] = $child;
    }

    /**
     * @throws ForkNotFoundException
     */
    private function get(int $pid): Fork {

        if (!isset($this->children[$pid])) {
            throw new ForkNotFoundException('Unknown fork');
        }

        $thread = $this->children[$pid];
        return $thread;
    }

    private function unlink(int $pid): void {
        unset($this->children[$pid]);
    }

    private function count(): int {
        return count($this->children);
    }
}