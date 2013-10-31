<?php

namespace Acme\Bundle\ApiBundle\Command;

use Supertag\Bundle\GearmanBundle\Command\GearmanJobCommandInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class DeployProjectCommand extends ContainerAwareCommand implements GearmanJobCommandInterface
{
    const NAME = 'job:deploy-project';

    /**
     * {@inheritDoc}
     */
    public function getNumRetries()
    {
        return 5;
    }

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Deploys a project')
            ->addArgument('id', InputArgument::REQUIRED, 'The project id')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Set this parameter to execute this action')
            ->setHelp(<<<EOF
The <info>%command.name%</info> deploys a project

<info>php %command.full_name%</info>
<info>php %command.full_name% --env=prod</info>
EOF
        );
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeLn("<comment>Successfully finished project deploy</comment>");
    }
}
