<?php

namespace Acme\ContactBundle\Command;

use Supertag\Bundle\GearmanBundle\Command\GearmanJobCommandInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FailingJobCommand extends ContainerAwareCommand implements GearmanJobCommandInterface
{
    const NAME = 'job:failing';

    /**
     * {@inheritDoc}
     */
    public function getNumRetries()
    {
        return 2;
    }

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Fails while processing')
            ->setHelp(<<<EOF
The <info>%command.name%</info> fails while processing

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
        throw new \Exception('ups I failed because of unexpected error');
    }
}
