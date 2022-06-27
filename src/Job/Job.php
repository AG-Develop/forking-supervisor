<?php

namespace AgDevelop\ForkingSupervisor\Job;

use AgDevelop\ForkingSupervisor\Exception\JobException;

abstract class Job implements JobInterface
{
    use JobTrait;
}