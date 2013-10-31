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
        $this->logger->err("Event: Job {$event->job->getName()} has failed");
    }
}
