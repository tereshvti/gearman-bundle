<?php

namespace Supertag\Bundle\GearmanBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use Exception;
use Supertag\Bundle\GearmanBundle\Command\GearmanJobCommandInterface;

class JobFailedEvent extends Event
{
    const NAME = 'supertag_gearman.job_failed_event';

    public $job, $workload, $exception;

    public function __construct(GearmanJobCommandInterface $job, $workload, Exception $exception)
    {
        $this->job = $job;
        $this->workload = $workload;
        $this->exception = $exception;
    }
}
