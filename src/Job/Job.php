<?php

namespace AgDevelop\JobSupervisor\Job;

use AgDevelop\JobSupervisor\Exception\JobException;

abstract class Job implements JobInterface
{
    use JobTrait;
}