<?php

include (__DIR__ . "/../vendor/autoload.php");

use AgDevelop\JobSupervisor\Exception\JobException;
use AgDevelop\JobSupervisor\Job\JobInterface;
use AgDevelop\JobSupervisor\Job\JobBuilder;
use AgDevelop\JobSupervisor\Job\JobTrait;
use AgDevelop\JobSupervisor\Fork\ForkBuilder;
use AgDevelop\JobSupervisor\Fork\ForkManager;
use AgDevelop\JobSupervisor\MonologLoggerProvider;
use AgDevelop\JobSupervisor\Supervisor;
use AgDevelop\JobSupervisor\Watchdog\WatchdogBuilder;
use Monolog\Handler\StreamHandler;
use Monolog\Level;

$logger = new \Monolog\Logger('default');
$handler = new StreamHandler('php://stderr', Level::Debug);
$logger->pushHandler($handler);

class ExampleJob implements JobInterface {

    use JobTrait;

    public function interrupt(): void
    {

    }

    public function run(): void
    {
        for ($i=1;$i<=rand(1,3);$i++) {
            sleep(rand(5,50));

            $this->getWatchdog()->setLastOccupied();

            if ($this->getWatchdog()->shouldExit()) {
                $this->logger?->info(sprintf('Watchdog says we(%s) should die so goodbye oh cruel world.', $this->getJobId()));
                throw new JobException('suicide',2);
            }
        }


        if (rand(0,3) == 1) {
            throw new JobException('error',1);
        }
    }
}

$manager = new ForkManager(
    20,
    new WatchdogBuilder(
        maxUnoccupiedTime: 30*1,
        maxAliveTime: 60,
    ),
    new ForkBuilder(new MonologLoggerProvider()),
    new JobBuilder(ExampleJob::class),
    $logger,
);

$s = new Supervisor(
    $manager,
    $logger
);

$s->run();
