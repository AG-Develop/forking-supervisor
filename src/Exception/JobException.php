<?php

namespace AgDevelop\JobSupervisor\Exception;

use JetBrains\PhpStorm\Internal\LanguageLevelTypeAware;

class JobException extends Exception
{
    public function __construct(string $message = "", int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function getReturnValue()
    {
        return $this->code;
    }
}