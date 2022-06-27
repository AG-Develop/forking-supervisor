<?php

namespace AgDevelop\ForkingSupervisor;

use Psr\Log\LoggerInterface;

interface LoggerProviderInterface
{
    public function provide($forceNewInstance = false): LoggerInterface;
}