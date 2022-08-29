<?php

namespace AgDevelop\ForkingSupervisor\Pcntl;

use AgDevelop\ForkingSupervisor\Exception\PcntlException;
use AgDevelop\ForkingSupervisor\Exception\UnknownReturnCodeException;
use AgDevelop\ForkingSupervisor\Exception\UnknownSignalException;

class PcntlProvider
{
    public function fork(): int
    {
        return pcntl_fork();
    }

    /**
     * @throws PcntlException
     * @throws UnknownReturnCodeException
     * @throws UnknownSignalException
     */
    public function wait(int $flags = 0): ?PcntlWaitResult
    {
        $status = 0;
        $pid = pcntl_wait($status, $flags);

        if (0 === $pid) {
            return null;
        }

        if ($pid < 0) {
            throw new PcntlException('pcntl_wait() returned -1');
        }

        $hasFinished = false;
        $returnCode = null;
        $termSignal = null;

        switch (true) {
            case pcntl_wifexited($status):
                $pcntlEvent = PcntlEvent::EXITED;
                $value = pcntl_wexitstatus($status);
                $returnCode = false !== $value ? $value
                    : throw new UnknownReturnCodeException('Unable to read return code');
                $hasFinished = true;
                break;
            case pcntl_wifsignaled($status):
                $pcntlEvent = PcntlEvent::SIGNALED;
                $value = pcntl_wtermsig($status);
                $termSignal = false !== $value ? $value
                    : throw new UnknownSignalException('Unable to determine termination signal');
                $hasFinished = true;
                break;
            case pcntl_wifstopped($status):
                $pcntlEvent = PcntlEvent::STOPPED;
                break;
            case pcntl_wifcontinued($status):
                $pcntlEvent = PcntlEvent::CONTINUED;
                break;
            default:
                throw new PcntlException(sprintf('Unknown pcntl event occurred (%s)', $status));
        }

        return new PcntlWaitResult($pid, $pcntlEvent, $hasFinished, $returnCode, $termSignal);
    }
}
