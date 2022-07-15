<?php

namespace AgDevelop\ForkingSupervisor\Exception;

use Throwable;

class JobException extends Exception
{
    public function __construct(string $message = '', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function getReturnValue(): int
    {
        return $this->code;
    }
}
