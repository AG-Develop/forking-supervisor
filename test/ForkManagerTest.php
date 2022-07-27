<?php

namespace AgDevelop\ForkingSupervisor\Test;

use AgDevelop\ForkingSupervisor\Fork;
use AgDevelop\ForkingSupervisor\ForkBuilder;
use AgDevelop\ForkingSupervisor\ForkManager;
use AgDevelop\ForkingSupervisor\Job\Job;
use AgDevelop\ForkingSupervisor\Job\JobQueuePullerInterface;
use AgDevelop\ForkingSupervisor\Pcntl\PcntlProvider;
use AgDevelop\ForkingSupervisor\Watchdog\Watchdog;
use AgDevelop\ForkingSupervisor\Watchdog\WatchdogBuilder;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ForkManagerTest extends TestCase
{

    public function testCleanSlots()
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
            ->onlyMethods(['kill','refillSlotWith'])
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
            $fork
        ]);

        $manager->cleanSlots();
    }

    public function testVacateSlotsWillFail()
    {
        $forkBuilder = $this->createMock(ForkBuilder::class);
        $watchdogBuilder = $this->createMock(WatchdogBuilder::class);
        $jobQueue = $this->createMock(JobQueuePullerInterface::class);
        $pcntl = $this->createPartialMock(PcntlProvider::class, ['wait']);
        $pcntl->expects($this->once())->method('wait')->willReturn(-1);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('error')->with('pcntl_wait() returned -1');

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

    public function testVacateSlotsWithNoChildrenExited()
    {
        $forkBuilder = $this->createMock(ForkBuilder::class);
        $watchdogBuilder = $this->createMock(WatchdogBuilder::class);
        $jobQueue = $this->createMock(JobQueuePullerInterface::class);
        $pcntl = $this->createPartialMock(PcntlProvider::class, ['wait']);
        $pcntl->expects($this->once())->method('wait')->willReturn(0);

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
                'returnedTrue' => 'wifexited',
                'statusMethod' => 'wexitstatus',
                'statusReturned' => 0,
                'failed' => false,
                'finished' => true,
                'shouldRetry' => true,
            ],
            [
                'returnedTrue' => 'wifsignaled',
                'statusMethod' => 'wtermsig',
                'statusReturned' => SIGHUP,
                'failed' => true,
                'finished' => true,
                'shouldRetry' => true,
            ],
            [
                'returnedTrue' => 'wifsignaled',
                'statusMethod' => 'wtermsig',
                'statusReturned' => SIGINT,
                'failed' => true,
                'finished' => true,
                'shouldRetry' => true,
            ],
            [
                'returnedTrue' => 'wifsignaled',
                'statusMethod' => 'wtermsig',
                'statusReturned' => SIGQUIT,
                'failed' => true,
                'finished' => true,
                'shouldRetry' => true,
            ],
            [
                'returnedTrue' => 'wifsignaled',
                'statusMethod' => 'wtermsig',
                'statusReturned' => SIGTERM,
                'failed' => true,
                'finished' => true,
                'shouldRetry' => true,
            ],
            [
                'returnedTrue' => 'wifsignaled',
                'statusMethod' => 'wtermsig',
                'statusReturned' => SIGKILL,
                'failed' => true,
                'finished' => true,
                'shouldRetry' => true,
            ],
            [
                'returnedTrue' => 'wifstopped',
                'statusMethod' => 'wstopsig',
                'statusReturned' => SIGSTOP,
                'failed' => false,
                'finished' => false,
                'shouldRetry' => false,
            ],
            [
                'returnedTrue' => 'wifcontinued',
                'statusMethod' => null,
                'statusReturned' => null,
                'failed' => false,
                'finished' => false,
                'shouldRetry' => false,
            ],
        ];
    }


    /** @dataProvider statusProvider */
    public function testVacateSlots($returnedTrue, $statusMethod, $statusReturned, $failed, $finished, $shouldRetry)
    {
        $pid = 1;
        $jobId = "2";
        $status = 0;
        $fork = $this->createMock(Fork::class);
        $job = $this->createMock(Job::class);

        $forkBuilder = $this->createMock(ForkBuilder::class);
        $watchdogBuilder = $this->createMock(WatchdogBuilder::class);
        $jobQueue = $this->createMock(JobQueuePullerInterface::class);

        $methods = [
            'wait',
            'wifexited',
            'wifsignaled',
            'wifstopped',
            'wifcontinued',
            ];

        if ($statusMethod)
            $methods[] = $statusMethod;

        $pcntl = $this->createPartialMock(PcntlProvider::class, $methods);

        // first call returns PID of the exited child
        // second call returns 0 as if no other child exited
        $pcntl->expects($this->exactly(2))->method('wait')->willReturnOnConsecutiveCalls($pid, 0);

        $pcntl->expects($this->once())->method($returnedTrue)->with($status)->willReturnCallback(function(&$status) {
            $status = 1;
            return true;
        });

        if ($statusMethod)
            $pcntl->expects($this->once())->method($statusMethod)->with($status)->willReturn($statusReturned);

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

        $manager->expects($this->once())->method('get')->with($pid)->willReturn($fork);
        $fork->expects($this->once())->method('getJob')->willReturn($job);
        $job->expects($this->any())->method('getJobId')->willReturn($jobId);

        if ($finished) {
            $manager->expects($this->once())->method('unlink')->with($pid);
        }

        if ($failed) {
            $job->expects($this->once())->method('shouldRetryOnFail')->willReturn($shouldRetry);
            if ($shouldRetry) {
                $job->expects($this->once())->method('incrementRetries');
                $manager->expects($this->once())->method('refillSlotWith')->with($job);
            }
        }

        $manager->vacateSlots();
    }

    public function testRefillSlotWith()
    {
    }

    public function testRefillVacatedSlots()
    {
    }
}
