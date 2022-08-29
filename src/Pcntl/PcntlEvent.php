<?php

namespace AgDevelop\ForkingSupervisor\Pcntl;

enum PcntlEvent
{
    case SIGNALED;
    case EXITED;
    case STOPPED;
    case CONTINUED;
}
