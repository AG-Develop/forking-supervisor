<?php

namespace AgDevelop\ForkingSupervisor\Test;

use AgDevelop\ForkingSupervisor\Exception\ForkFailedException;
use AgDevelop\ForkingSupervisor\Exception\JobException;
use AgDevelop\ForkingSupervisor\Fork;
use AgDevelop\ForkingSupervisor\ForkBuilder;
use AgDevelop\ForkingSupervisor\Job\JobInterface;
use AgDevelop\ForkingSupervisor\Pcntl\PcntlProvider;
use AgDevelop\ForkingSupervisor\Watchdog\WatchdogInterface;
use AgDevelop\Interface\Logger\LoggerProviderInterface;
use PHPUnit\Framework\TestCase;

class ForkBuilderTest extends TestCase
{

    public function testBuildForkFailed()
    {
        $loggerProviderMock = $this->createMock(LoggerProviderInterface::class);
        $job = $this->createMock(JobInterface::class);
        $watchdog = $this->createMock(WatchdogInterface::class);
        $pcntlMock = $this->createMock(PcntlProvider::class);

        $pcntlMock->expects($this->once())->method('fork')->willReturn(-1);

        $builder = new ForkBuilder(
            $pcntlMock,
            $loggerProviderMock,
        );

        $this->expectException(ForkFailedException::class);
        $builder->build($job, $watchdog);
    }

    public function testBuildSucceededParentReturnsFork()
    {
        $loggerProviderMock = $this->createMock(LoggerProviderInterface::class);
        $job = $this->createMock(JobInterface::class);
        $watchdog = $this->createMock(WatchdogInterface::class);
        $pcntlMock = $this->createMock(PcntlProvider::class);

        $pcntlMock->expects($this->once())->method('fork')->willReturn(1);

        $builder = new ForkBuilder(
            $pcntlMock,
            $loggerProviderMock,
        );

        $fork = $builder->build($job, $watchdog);

        $this->assertInstanceOf(Fork::class, $fork);
    }

    public function testBuildSucceededChildRunsJobAndExits()
    {
        $loggerProviderMock = $this->createMock(LoggerProviderInterface::class);

        $job = $this->createMock(JobInterface::class);
        $job->expects($this->once())->method('run');

        $watchdog = $this->createMock(WatchdogInterface::class);

        $pcntlMock = $this->createMock(PcntlProvider::class);

        $pcntlMock->expects($this->once())->method('fork')->willReturn(0);

        $builder = $this->getMockBuilder(ForkBuilder::class)
            ->setConstructorArgs([$pcntlMock, $loggerProviderMock])
            ->onlyMethods(['exit'])
            ->getMock();

        $builder->expects($this->once())->method('exit')->with(0);

        $builder->build($job, $watchdog);
    }

    public function testBuildSucceededChildRunsJobWhichFailsWithException()
    {
        $loggerProviderMock = $this->createMock(LoggerProviderInterface::class);

        $job = $this->createMock(JobInterface::class);
        $job->expects($this->once())->method('run')->willThrowException(new \Exception('Error'));

        $watchdog = $this->createMock(WatchdogInterface::class);

        $pcntlMock = $this->createMock(PcntlProvider::class);

        $pcntlMock->expects($this->once())->method('fork')->willReturn(0);

        $builder = $this->getMockBuilder(ForkBuilder::class)
            ->setConstructorArgs([$pcntlMock, $loggerProviderMock])
            ->onlyMethods(['exit'])
            ->getMock();

        $builder->expects($this->once())->method('exit')->with(1);

        $builder->build($job, $watchdog);
    }

    public function testBuildSucceededChildRunsJobWhichFailsWithJobException()
    {
        $loggerProviderMock = $this->createMock(LoggerProviderInterface::class);

        $job = $this->createMock(JobInterface::class);
        $job->expects($this->once())->method('run')->willThrowException(new JobException('Error', 5));

        $watchdog = $this->createMock(WatchdogInterface::class);

        $pcntlMock = $this->createMock(PcntlProvider::class);

        $pcntlMock->expects($this->once())->method('fork')->willReturn(0);

        $builder = $this->getMockBuilder(ForkBuilder::class)
            ->setConstructorArgs([$pcntlMock, $loggerProviderMock])
            ->onlyMethods(['exit'])
            ->getMock();

        $builder->expects($this->once())->method('exit')->with(5);

        $builder->build($job, $watchdog);
    }
}
