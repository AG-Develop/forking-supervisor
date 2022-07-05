<?php

namespace AgDevelop\ForkingSupervisor\Job;

interface JobBuilderInterface
{
    public function build(): JobInterface|null;
}