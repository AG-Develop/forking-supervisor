<?php

namespace AgDevelop\JobSupervisor;

use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Psr\Log\LoggerInterface;

/**
 * Logger provider's aim is to provide separate logger instances for forked processes
 */
class MonologLoggerProvider implements LoggerProviderInterface
{
    private static ?LoggerInterface $logger = null;

    public function provide($forceNewInstance = false): LoggerInterface
    {
        if ($forceNewInstance) {
            return $this->getNewLogger();
        }

        if (self::$logger === null) {
            self::$logger = $this->getNewLogger();
        }

        return self::$logger;
    }

    private function getNewLogger() {
        $handler = new StreamHandler('php://stderr', Level::Debug);
        $logger = new \Monolog\Logger('default');
        $logger->pushHandler($handler);

        return $logger;
    }
}