<?php

namespace AgDevelop\ForkingSupervisor;

use AgDevelop\Interface\Logger\LoggerProviderInterface;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Psr\Log\LoggerInterface;

/**
 * Logger provider's aim is to provide separate logger instances for forked processes
 */
class MonologLoggerProvider implements LoggerProviderInterface
{
    private LoggerInterface $logger;

    public function getNewLogger(): LoggerInterface {
        $handler = new StreamHandler('php://stderr', Level::Debug);
        $logger = new \Monolog\Logger('default');
        $logger->pushHandler($handler);

        return $logger;
    }
}