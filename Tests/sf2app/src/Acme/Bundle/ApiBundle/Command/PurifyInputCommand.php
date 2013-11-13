<?php

namespace Acme\Bundle\ApiBundle\Command;

use Supertag\Bundle\GearmanBundle\Command\GearmanJobCommandInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class PurifyInputCommand extends ContainerAwareCommand implements GearmanJobCommandInterface
{
    const NAME = 'job:purify-input';

    /**
     * {@inheritDoc}
     */
    public function getNumRetries()
    {
        return 1;
    }

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Deploys a project')
            ->addArgument('entity', InputArgument::REQUIRED, 'Entity to purify')
            ->addOption('fields', null, InputOption::VALUE_REQUIRED, 'Fields to purify, separated by space')
            ->addOption('opt', null, InputOption::VALUE_REQUIRED, 'A simple option')
            ->setHelp(<<<EOF
The <info>%command.name%</info> purifies entity fields

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
        $output->writeLn("Received argument <info>entity</info> as <comment>".$input->getArgument('entity')."</comment>");
        $output->writeLn("Received option <info>fields</info> as <comment>".$input->getOption('fields')."</comment>");
        $output->writeLn("Received option <info>opt</info> as <comment>".$input->getOption('opt')."</comment>");
        $output->writeLn("<comment>Successfully finished purified</comment>");
    }
}
