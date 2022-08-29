<?php

namespace AgDevelop\ForkingSupervisor;

use AgDevelop\ForkingSupervisor\Exception\ForkFailedException;
use AgDevelop\ForkingSupervisor\Exception\JobException;
use AgDevelop\ForkingSupervisor\Job\JobInterface;
use AgDevelop\ForkingSupervisor\Pcntl\PcntlProvider;
use AgDevelop\ForkingSupervisor\Watchdog\WatchdogInterface;
use AgDevelop\Interface\Logger\LoggerProviderInterface;
use Exception;

class ForkBuilder implements ForkBuilderInterface
{
    public function __construct(
        private PcntlProvider $pcntlProvider,
        private LoggerProviderInterface $loggerProvider,
    ) {
    }

    /**
     * @throws ForkFailedException
     */
    public function build(JobInterface $job, WatchdogInterface $watchdog): Fork
    {
        $ret = $this->pcntlProvider->fork();

        switch ($ret) {
            case -1:
                throw new ForkFailedException();
            case 0:
                // we're child
                try {
                    $job->setWatchdog($watchdog);

                    // always provides new logger to forked process
                    $job->setLogger($this->loggerProvider->getNewLogger());

                    $job->run();

                    // exit anyway to prevent running parent's code
                    $this->exit(0);
                } catch (JobException $e) {
                    $this->exit($e->getReturnValue());
                } catch (Exception $e) {
                    // returns 1 by default to report error
                    $this->exit(1);
                }
            default:
                // we're parent process
                return new Fork($ret, $job, $watchdog);
        }
    }

    public function exit(int $result): void
    {
        exit($result);
    }
}
