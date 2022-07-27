<?php

namespace AgDevelop\ForkingSupervisor\Pcntl;

class PcntlProvider
{
    public function fork(): int
    {
        return pcntl_fork();
    }

    public function wait(&$status, $flags): int
    {
        return pcntl_wait($status, $flags);
    }

    public function wifsignaled($status): bool
    {
        return pcntl_wifsignaled($status);
    }

    public function wifexited($status): bool
    {
        return pcntl_wifexited($status);
    }

    public function wexitstatus(int $status): int|false
    {
        return pcntl_wexitstatus($status);
    }

    public function wifstopped($status): bool
    {
        return pcntl_wifstopped($status);
    }

    public function wstopsig(int $status): int|false
    {
        return pcntl_wstopsig($status);
    }

    public function wtermsig(int $status): int|false
    {
        return pcntl_wtermsig($status);
    }

    public function wifcontinued($status): bool
    {
        return pcntl_wifcontinued($status);
    }
}
