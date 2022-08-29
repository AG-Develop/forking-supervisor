<?php

namespace AgDevelop\ForkingSupervisor\Test;

use AgDevelop\ForkingSupervisor\Exception\PcntlException;
use AgDevelop\ForkingSupervisor\Fork;
use AgDevelop\ForkingSupervisor\ForkBuilder;
use AgDevelop\ForkingSupervisor\ForkManager;
use AgDevelop\ForkingSupervisor\Job\Job;
use AgDevelop\ForkingSupervisor\Job\JobQueuePullerInterface;
use AgDevelop\ForkingSupervisor\Pcntl\PcntlEvent;
use AgDevelop\ForkingSupervisor\Pcntl\PcntlProvider;
use AgDevelop\ForkingSupervisor\Pcntl\PcntlWaitResult;
use AgDevelop\ForkingSupervisor\Watchdog\Watchdog;
use AgDevelop\ForkingSupervisor\Watchdog\WatchdogBuilder;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ForkManagerTest extends TestCase
{
    public function testCleanSlots(): void
    {
        $fork = $this->createMock(Fork::class);
        $watchdog = $this->createMock(Watchdog::class);
        $job = $this->createMock(Job::class);

        $forkBuilder = $this->createMock(ForkBuilder::class);
        $watchdogBuilder = $this->createMock(WatchdogBuilder::class);
        $jobQueue = $this->createMock(JobQueuePullerInterface::class);
        $pcntl = $this->createMock(PcntlProvider::class);
        $logger = $this->createMock(LoggerInterface::class);

        $watchdog->expects($this->once())->method('shouldBeTerminated')->willReturn(true);
        $fork->expects($this->once())->method('getWatchdog')->willReturn($watchdog);
        $fork->expects($this->once())->method('getJob')->willReturn($job);

        $manager = $this->getMockBuilder(ForkManager::class)
            ->onlyMethods(['kill', 'refillSlotWith'])
            ->setConstructorArgs([
                1,
                $watchdogBuilder,
                $forkBuilder,
                $jobQueue,
                $pcntl,
                $logger,
            ])
            ->getMock();

        $manager->expects($this->once())->method('kill')->with($fork);
        $manager->expects($this->once())->method('refillSlotWith')->with($job);

        $reflection = new \ReflectionClass(ForkManager::class);
        $reflection_property = $reflection->getProperty('children');
        $reflection_property->setAccessible(true);
        $reflection_property->setValue($manager, [
            $fork,
        ]);

        $manager->cleanSlots();
    }

    public function testVacateSlotsWillFail(): void
    {
        $forkBuilder = $this->createMock(ForkBuilder::class);
        $watchdogBuilder = $this->createMock(WatchdogBuilder::class);
        $jobQueue = $this->createMock(JobQueuePullerInterface::class);
        $pcntl = $this->createPartialMock(PcntlProvider::class, ['wait']);
        $pcntl->expects($this->once())->method('wait')->will($this->throwException(new PcntlException('pcntl_wait() returned -1')));

        $logger = $this->createMock(LoggerInterface::class);

        $manager = $this->getMockBuilder(ForkManager::class)
            ->onlyMethods(['count'])
            ->setConstructorArgs([
                1,
                $watchdogBuilder,
                $forkBuilder,
                $jobQueue,
                $pcntl,
                $logger,
            ])
            ->getMock();

        $manager->expects($this->once())->method('count')->willReturn(1);

        $manager->vacateSlots();
    }

    public function testVacateSlotsWithNoChildrenExited(): void
    {
        $forkBuilder = $this->createMock(ForkBuilder::class);
        $watchdogBuilder = $this->createMock(WatchdogBuilder::class);
        $jobQueue = $this->createMock(JobQueuePullerInterface::class);
        $pcntl = $this->createPartialMock(PcntlProvider::class, ['wait']);
        $pcntl->expects($this->once())->method('wait')->willReturn(null);

        $logger = $this->createMock(LoggerInterface::class);

        $manager = $this->getMockBuilder(ForkManager::class)
            ->onlyMethods(['count'])
            ->setConstructorArgs([
                1,
                $watchdogBuilder,
                $forkBuilder,
                $jobQueue,
                $pcntl,
                $logger,
            ])
            ->getMock();

        $manager->expects($this->once())->method('count')->willReturn(1);

        $manager->vacateSlots();
    }

    public function statusProvider()
    {
        return [
            [
                'pcntlEvent' => PcntlEvent::EXITED,
                'hasFinished' => true,
                'hasFailed' => false,
                'returnCode' => 0,
                'termSignal' => null,
                'shouldRetry' => false,
            ],
            [
                'pcntlEvent' => PcntlEvent::EXITED,
                'hasFinished' => true,
                'hasFailed' => true,
                'returnCode' => 1,
                'termSignal' => null,
                'shouldRetry' => true,
            ],
            [
                'pcntlEvent' => PcntlEvent::SIGNALED,
                'hasFinished' => true,
                'hasFailed' => true,
                'returnCode' => 0,
                'termSignal' => SIGKILL,
                'shouldRetry' => true,
            ],
            [
                'pcntlEvent' => PcntlEvent::STOPPED,
                'hasFinished' => false,
                'hasFailed' => false,
                'returnCode' => null,
                'termSignal' => null,
                'shouldRetry' => false,
            ],
            [
                'pcntlEvent' => PcntlEvent::CONTINUED,
                'hasFinished' => false,
                'hasFailed' => false,
                'returnCode' => null,
                'termSignal' => null,
                'shouldRetry' => false,
            ],
        ];
    }

    /** @dataProvider statusProvider */
    public function testVacateSlots(PcntlEvent $pcntlEvent, bool $hasFinished, bool $hasFailed, ?int $returnCode, ?int $termSignal, bool $shouldRetry): void
    {
        $pid = 1;
        $jobId = '2';
        $status = 0;
        $fork = $this->createMock(Fork::class);
        $job = $this->createMock(Job::class);

        $forkBuilder = $this->createMock(ForkBuilder::class);
        $watchdogBuilder = $this->createMock(WatchdogBuilder::class);
        $jobQueue = $this->createMock(JobQueuePullerInterface::class);

        $pcntl = $this->getMockBuilder(PcntlProvider::class)
            ->onlyMethods(['wait'])
            ->disableArgumentCloning()
            ->getMock();

        // first call returns PID of the exited child
        // second call returns 0 as if no other child exited
        $pcntl->expects($this->exactly(2))->method('wait')->willReturnOnConsecutiveCalls(
            new PcntlWaitResult(1, $pcntlEvent, $hasFinished, $returnCode, $termSignal),
            null,
        );

        $logger = $this->createMock(LoggerInterface::class);

        $manager = $this->getMockBuilder(ForkManager::class)
            ->onlyMethods(['count', 'get', 'unlink', 'refillSlotWith'])
            ->setConstructorArgs([
                1,
                $watchdogBuilder,
                $forkBuilder,
                $jobQueue,
                $pcntl,
                $logger,
            ])
            ->getMock();

        $manager->expects($this->exactly(2))->method('count')->willReturn(1);

        if ($hasFinished) {
            $manager->expects($this->once())->method('get')->with($pid)->willReturn($fork);
            $fork->expects($this->once())->method('getJob')->willReturn($job);
            $job->expects($this->any())->method('getJobId')->willReturn($jobId);
            $manager->expects($this->once())->method('unlink')->with($pid);
        }

        if ($hasFailed) {
            $job->expects($this->once())->method('shouldRetryOnFail')->willReturn($shouldRetry);
            if ($shouldRetry) {
                $job->expects($this->once())->method('incrementRetries');
                $manager->expects($this->once())->method('refillSlotWith')->with($job);
            }
        }

        $manager->vacateSlots();
    }

    public function refillVacatedSlotsProvider(): array
    {
        return [
            [10, 3, 5],
            [10, 3, 50],
        ];
    }

    /** @dataProvider refillVacatedSlotsProvider */
    public function testRefillVacatedSlots(int $slots, int $jobsInProgress, int $jobsInQueue): void
    {
        $freeSlots = $slots - $jobsInProgress;
        $jobsToFork = min($jobsInQueue, $freeSlots);

        $job = $this->createMock(Job::class);
        $forkBuilder = $this->createMock(ForkBuilder::class);
        $watchdogBuilder = $this->createMock(WatchdogBuilder::class);
        $jobQueue = $this->createMock(JobQueuePullerInterface::class);

        $jobs = [];
        for ($i = 1; $i <= $jobsToFork; ++$i) {
            $jobs[] = $job;
        }
        $jobs[] = null;

        $jobQueue->expects($this->exactly($jobsToFork + ($jobsInQueue > $freeSlots ? 0 : 1)))->method('pull')->willReturnOnConsecutiveCalls(...$jobs);

        $pcntl = $this->createMock(PcntlProvider::class);
        $logger = $this->createMock(LoggerInterface::class);

        $manager = $this->getMockBuilder(ForkManager::class)
            ->onlyMethods(['refillSlotWith', 'count'])
            ->setConstructorArgs([
                $slots,
                $watchdogBuilder,
                $forkBuilder,
                $jobQueue,
                $pcntl,
                $logger,
            ])
            ->getMock();

        $manager->expects($this->exactly($jobsToFork))->method('refillSlotWith')->with($job);

        $returnValues = [];
        for ($i = $jobsInProgress; $i <= $slots; ++$i) {
            $returnValues[] = $i;
        }

        $manager->expects($this->exactly($jobsToFork + 1))->method('count')->willReturnOnConsecutiveCalls(...$returnValues);

        $manager->refillVacatedSlots();
    }
}
