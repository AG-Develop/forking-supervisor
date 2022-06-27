<?php

namespace AgDevelop\JobSupervisor\Job;

interface JobBuilderInterface
{
    public function build(): JobInterface;
}