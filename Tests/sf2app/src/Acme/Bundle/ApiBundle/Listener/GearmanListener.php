<?php

namespace Acme\Bundle\ApiBundle\Listener;

use Supertag\Bundle\GearmanBundle\Event\JobFailedEvent;

class GearmanListener
{
    public function onJobFailed(JobFailedEvent $event)
    {
        var_dump('triggered');
    }
}
