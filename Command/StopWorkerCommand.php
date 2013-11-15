<?php

namespace Supertag\Bundle\GearmanBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\ProcessBuilder;

class StopWorkerCommand extends ContainerAwareCommand
{
    const NAME = 'supertag:gearman:stop-worker';

    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Stop supervized gearman workers')
            ->addOption('force', null, InputOption::VALUE_NONE, "Force killing workers even if they have jobs running")
            ->setHelp(<<<EOF
The <info>%command.name%</info> command gracefully stops gearman workers, which were
launched using supervisor config. Waits until all jobs are done and stops process unless
<comment>force</comment> option is used.

<info>php %command.full_name%</info>
<info>php %command.full_name% --env=prod</info>
EOF
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('force')) {
            $this->doKill($output);
            return;
        }

        // first wait until all jobs are processed
        $pb = new ProcessBuilder();

        // PHP wraps the process in "sh -c" by default, but we need to control
        // the process directly.
        if (!defined('PHP_WINDOWS_VERSION_MAJOR')) {
            $pb->add('exec');
        }

        $pb->add('php')
            ->add($this->getContainer()->getParameter('kernel.root_dir').'/console')
            ->add('supertag:gearman:monitor')
            ->add('--env=' . $input->getOption('env'));

        if ($input->getOption('verbose')) {
            $pb->add('--verbose');
        }

        $output->write("Waiting for all jobs to finish: ");
        $ns = $this->getContainer()->getParameter('supertag_gearman.namespace');
        while(true) {
            $data = '';
            $cb = function($type, $text) use(&$data) {
                $data .= $text;
            };
            // run the job command
            if (0 !== $pb->getProcess()->run($cb)) {
                throw new \RuntimeException("Failed while attempting to get gearman server stats");
            }
            $lines = explode("\n", $data);
            // filter jobs by namespace
            $lines = array_filter($lines, function($line) use($ns) {
                return strpos($line, $ns) !== false;
            });
            // filter jobs active jobs
            $lines = array_filter($lines, function($line) {
                $parts = explode(' ', trim($line));
                return intval($parts[2]) !== 0 || intval($parts[4]) !== 0;
            });
            if (!count($lines)) {
                $output->writeLn("\n");
                $output->writeLn("There are no active jobs running, will kill workers..");
                $this->doKill($output);
                break;
            }
            sleep(1);
            $output->write(".");
        }
    }

    /**
     * Will work only on linux or unix
     */
    protected function doKill(OutputInterface $output)
    {
        $projectDir = dirname($this->getContainer()->getParameter('kernel.root_dir'));
        if (file_exists($pidFile = $projectDir . '/supervisord.pid')) {
            $pid = trim(file_get_contents($pidFile));
            $output->writeLn("Found supervisor pid file, killing process: <comment>{$pid}</comment>");
            exec("kill {$pid}");
        } else {
            $output->writeLn("Could not find supervisor pid file, probably a worker is not running.");
        }
    }
}
