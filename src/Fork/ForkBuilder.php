<?php

namespace AgDevelop\JobSupervisor\Fork;

use AgDevelop\JobSupervisor\Exception\ForkFailedException;
use AgDevelop\JobSupervisor\Exception\JobException;
use AgDevelop\JobSupervisor\Job\JobInterface;
use AgDevelop\JobSupervisor\LoggerProviderInterface;
use AgDevelop\JobSupervisor\Watchdog\WatchdogBuilder;
use AgDevelop\JobSupervisor\Watchdog\WatchdogInterface;

class ForkBuilder implements ForkBuilderInterface
{
    public function __construct(
        private LoggerProviderInterface $loggerProvider,
    ) {

    }

    /**
     * @throws ForkFailedException
     */
    public function build(JobInterface $job, WatchdogInterface $watchdog): Fork {

        $ret = pcntl_fork();

        switch ($ret) {
            case -1:
                throw new ForkFailedException();
            case 0:
                // we're child
                try {
                    $job->setWatchdog($watchdog);
                    $job->setLogger($this->loggerProvider->provide(
                        forceNewInstance: true));
                    $job->run();
                } catch (JobException $e) {
                    exit($e->getReturnValue());
                } catch (\Exception $e) {
                    // returns 1 by default to report error
                    exit(1);
                }

                // exit anyway to prevent running parent's code
                exit(0);
            default:
                // we're parent process
                return new Fork($ret, $job, $watchdog);
        }
    }
}