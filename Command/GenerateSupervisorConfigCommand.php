<?php

namespace Supertag\Bundle\GearmanBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateSupervisorConfigCommand extends ContainerAwareCommand
{
    const NAME = 'supertag:gearman:generate-supervisor-config';

    const TEMPLATE_MAIN = <<<EOF
[supervisord]
logfile=%root_dir%/logs/supervisord.log
logfile_maxbytes=50MB
logfile_backups=10
loglevel=info
pidfile=%project_dir%/supervisord.pid
nodaemon=false
minfds=1024
minprocs=200

%workers%
EOF;

    const TEMPLATE_WORKER = <<<EOF
[program:worker%num_worker%]
command=/usr/bin/php %project_dir%/app/console supertag:gearman:run-worker --env=%env%
process_name=%namespace%gearman_worker%num_worker%
numprocs=1
directory=%project_dir%
autostart=true
autorestart=true
user=%user%
stdout_logfile=%root_dir%/logs/gearman-worker%num_worker%.info.log
stdout_logfile_maxbytes=1MB
stderr_logfile=%root_dir%/logs/gearman-worker%num_worker%.error.log
stderr_logfile_maxbytes=1MB
EOF;

    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Generate a supervisor config, based on current symfony2 project')
            ->addArgument('user', InputArgument::REQUIRED)
            ->addOption('num-workers', null, InputOption::VALUE_REQUIRED, "Specifies a number of parallel workers")
            ->setHelp(<<<EOF
The <info>%command.name%</info> command generates supervisor config to watch
over gearman workers, respawn them in case of unexpected errors:

<info>php %command.full_name% www-data</info>
<info>php %command.full_name% --env=prod www-data --num-worrkers=2</info>
EOF
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $user = $input->getArgument('user');
        $project_dir = realpath($this->getContainer()->getParameter('kernel.root_dir') . '/..');
        $root_dir = realpath($this->getContainer()->getParameter('kernel.root_dir'));
        $env = $this->getContainer()->getParameter('kernel.environment');
        $namespace = $this->getContainer()->getParameter('supertag_gearman.namespace');

        $numWorkers = $input->hasOption('num-workers') ? intval($input->getOption('num-workers')) : 1;
        if ($numWorkers <= 0) {
            $numWorkers = 1;
        }
        $workers = '';
        $replace = compact('project_dir', 'root_dir', 'user', 'env', 'namespace');
        for ($i = 0; $i < $numWorkers; $i++) {
            $replace['num_worker'] = $i + 1;
            $workers .= ($i === 0 ? '' : "\n\n") . str_replace(
                array_map(function($key) { return '%'.$key.'%'; }, array_keys($replace)),
                array_values($replace),
                self::TEMPLATE_WORKER
            );
        }

        $replace['workers'] = $workers;
        $content = str_replace(
            array_map(function($key) { return '%'.$key.'%'; }, array_keys($replace)),
            array_values($replace),
            self::TEMPLATE_MAIN
        );

        $outputFile = $project_dir . '/worker-supervisor.conf';
        if (file_put_contents($outputFile, $content) === false) {
            throw new \RuntimeException("Failed to write into {$outputFile}");
        }
        $output->writeLn("Generated <info>{$outputFile}</info>, use it like:");
        $output->writeLn("    <comment>supervisord -n -c {$outputFile}</comment> - for debug mode, will do output when started");
        $output->writeLn("    <comment>supervisord -c {$outputFile}</comment> - for production mode, to run in background");
    }
}
