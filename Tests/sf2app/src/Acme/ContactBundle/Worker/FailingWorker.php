<?php

namespace Acme\ContactBundle\Worker;

use GearmanJob;
use Symfony\Component\Console\Output\OutputInterface;

use Supertag\Bundle\GearmanBundle\Annotation as Gearman;

class FailingWorker
{
    /**
     * @Gearman\Job(name="failing.gearman.job", retries=5, description="Describe failing gearman job")
     */
    public function myFailingGearmanJob(GearmanJob $job, OutputInterface $output)
    {
        throw new \Exception('ups I failed because of unexpected error');
    }

    /**
     * @Gearman\Job(name="sleepy.gearman.job", description="Describe sleepy gearman job")
     */
    public function mySleepyGearmanJob(GearmanJob $job, OutputInterface $output)
    {
        sleep(2);
        $output->writeLn("<comment>Successfully finished sleepy.gearman.job</comment>");
    }

    /**
     * @Gearman\Job(name="high.gearman.job", description="Describe sleepy gearman job")
     */
    public function myHighGearmanJob(GearmanJob $job, OutputInterface $output)
    {
        sleep(5);
        $output->writeLn("<comment>Successfully finished high.gearman.job</comment>");
    }
}
