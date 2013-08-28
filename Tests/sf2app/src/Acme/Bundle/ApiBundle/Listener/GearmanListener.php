<?php

namespace Acme\Bundle\ApiBundle\Listener;

use Supertag\Bundle\GearmanBundle\Event\JobFailedEvent;

class GearmanListener
{
    private $logger;

    public function __construct($logger)
    {
        $this->logger = $logger;
    }

    public function onJobFailed(JobFailedEvent $event)
    {
        $this->logger->err("Job {$event->jobName} has failed, while processing: {$event->workload}");
    }
}
