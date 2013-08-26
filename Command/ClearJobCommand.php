<?php

namespace Supertag\Bundle\GearmanBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\ProgressHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use GearmanWorker;

/**
 * Sources are mainly authored by <https://github.com/hautelook/GearmanBundle>
 */
class ClearJobCommand extends ContainerAwareCommand
{
    const NAME = "supertag:gearman:clear-job";

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Clear a gearman queue for the given job')
            ->addArgument(
                'job_name',
                InputArgument::REQUIRED,
                'The name of the gearman job to clear'
            )->setHelp(<<<EOF
The <info>%command.name%</info> command clears a gearman job queue for
the given job name

<info>php %command.full_name% job_name</info>
<info>php %command.full_name% job_name --env=prod</info>
EOF
);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $jobName = $input->getArgument('job_name');
        $gmc = $this->getContainer()->get('supertag_gearman.client');

        $worker = new GearmanWorker($this->getContainer()->getParameter('supertag_gearman.servers'));
        $worker->addCallbackFunction($gmc->getJobName($jobName), function() {
            // do nothing
        });

        $output->writeln("<info>Noop worker created</info> for job <comment>{$jobName}</comment>");
        /** @var $progress \Symfony\Component\Console\Helper\ProgressHelper */
        $progress = $this->getHelperSet()->get('progress');
        $progress->setRedrawFrequency(10);

        $progress->setFormat(ProgressHelper::FORMAT_VERBOSE_NOMAX);
        $progress->start($output);

        while ($worker->work()) {
            $progress->advance();
        }
        $progress->finish();
    }
}
