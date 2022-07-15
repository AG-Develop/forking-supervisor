<?php

include(__DIR__ . "/../vendor/autoload.php");

use AgDevelop\ForkingSupervisor\Exception\JobException;
use AgDevelop\ForkingSupervisor\Fork\ForkBuilder;
use AgDevelop\ForkingSupervisor\Fork\ForkManager;
use AgDevelop\ForkingSupervisor\ForkSupervisor;
use AgDevelop\ForkingSupervisor\Job\JobInterface;
use AgDevelop\ForkingSupervisor\Job\JobQueuePuller;
use AgDevelop\ForkingSupervisor\Job\JobTrait;
use AgDevelop\ForkingSupervisor\MonologLoggerProvider;
use AgDevelop\ForkingSupervisor\Watchdog\WatchdogBuilder;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;

$logger = new Logger('default');
$handler = new StreamHandler('php://stderr', Level::Debug);
$logger->pushHandler($handler);

class CustomJobQueue extends JobQueuePuller
{
    public function pull(): JobInterface|null
    {
        $job = parent::pull();

        // simulate no job in queue
        if (rand(0, 5) == 3) {
            return null;
        }

        return $job;
    }
}

class ExampleJob implements JobInterface
{
    use JobTrait;

    public function interrupt(): void
    {
    }

    public function run(): void
    {
        for ($i = 1; $i <= rand(1, 3); $i++) {
            sleep(rand(5, 50));

            $this->getWatchdog()->setLastOccupied();

            if ($this->getWatchdog()->shouldExit()) {
                $this->logger?->info(
                    sprintf('Watchdog says we - job %s - should die so goodbye oh cruel world.', $this->getJobId())
                );
                throw new JobException('suicide', 2);
            }
        }


        if (rand(0, 3) == 1) {
            throw new JobException('error', 1);
        }
    }
}

$manager = new ForkManager(
    20,
    new WatchdogBuilder(
        maxUnoccupiedTime: 30 * 1,
        maxAliveTime: 60,
    ),
    new ForkBuilder(new MonologLoggerProvider()),
    new CustomJobQueue(ExampleJob::class),
    $logger,
);

$s = new ForkSupervisor(
    $manager,
    $logger
);

$s->run();
