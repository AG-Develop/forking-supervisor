<?php

namespace AgDevelop\ForkingSupervisor\Test;

use AgDevelop\ForkingSupervisor\MonologLoggerProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class MonologLoggerProviderTest extends TestCase
{

    public function testGetNewLogger()
    {
        $provider = new MonologLoggerProvider();
        $logger = $provider->getNewLogger();

        $this->assertInstanceOf(LoggerInterface::class, $logger);

        $logger2 = $provider->getNewLogger();

        $this->assertNotSame($logger, $logger2);
    }
}
