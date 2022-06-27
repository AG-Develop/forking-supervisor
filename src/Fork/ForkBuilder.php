<?php

namespace AgDevelop\ForkingSupervisor\Fork;

use AgDevelop\ForkingSupervisor\Exception\ForkFailedException;
use AgDevelop\ForkingSupervisor\Exception\JobException;
use AgDevelop\ForkingSupervisor\Job\JobInterface;
use AgDevelop\ForkingSupervisor\LoggerProviderInterface;
use AgDevelop\ForkingSupervisor\Watchdog\WatchdogInterface;

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