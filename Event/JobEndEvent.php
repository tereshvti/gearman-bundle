<?php

namespace Supertag\Bundle\GearmanBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class JobEndEvent extends Event
{
    const NAME = 'supertag_gearman.job_end_event';

    public $jobName, $metadata, $workload;

    public function __construct($jobName, array $metadata, $workload)
    {
        $this->jobName = $jobName;
        $this->metadata = $metadata;
        $this->workload = $workload;
    }
}
