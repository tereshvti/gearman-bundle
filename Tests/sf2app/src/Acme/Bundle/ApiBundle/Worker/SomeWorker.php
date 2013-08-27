<?php

namespace Acme\Bundle\ApiBundle\Worker;

use GearmanJob;
use Symfony\Component\Console\Output\OutputInterface;

use Supertag\Bundle\GearmanBundle\Annotation as Gearman;

class SomeWorker
{
    /**
     * @Gearman\Job(name="normal.gearman.job", description="Describe normal gearman job")
     */
    public function myGearmanJob(GearmanJob $job, OutputInterface $output)
    {
        $output->writeLn("<comment>Successfully finished normal.gearman.job</comment> worload <info>{$job->workload()}</info>");
    }
}
