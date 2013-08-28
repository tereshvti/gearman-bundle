<?php

namespace Supertag\Bundle\GearmanBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use Exception;

class JobFailedEvent extends Event
{
    const NAME = 'supertag_gearman.job_failed_event';

    public $jobName, $metadata, $exception, $workload;

    public function __construct($jobName, array $metadata, $workload, Exception $exception)
    {
        $this->jobName = $jobName;
        $this->metadata = $metadata;
        $this->workload = $workload;
        $this->exception = $exception;
    }
}
