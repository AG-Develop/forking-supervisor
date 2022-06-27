<?php

namespace AgDevelop\JobSupervisor;

use Psr\Log\LoggerInterface;

interface LoggerProviderInterface
{
    public function provide($forceNewInstance = false): LoggerInterface;
}