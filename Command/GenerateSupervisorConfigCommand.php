<?php

namespace Supertag\Bundle\GearmanBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateSupervisorConfigCommand extends ContainerAwareCommand
{
    const NAME = 'supertag:gearman:generate-supervisor-config';

    const TEMPLATE = <<<EOF
[unix_http_server]
file=%project_dir%/supervisord.sock

[supervisord]
logfile=%root_dir%/logs/supervisord.log
logfile_maxbytes=50MB
logfile_backups=10
loglevel=info
pidfile=%project_dir%/supervisord.pid
nodaemon=false
minfds=1024
minprocs=200

; the below section must remain in the config file for RPC
; (supervisorctl/web interface) to work, additional interfaces may be
; added by defining them in separate rpcinterface: sections
[rpcinterface:supervisor]
supervisor.rpcinterface_factory = supervisor.rpcinterface:make_main_rpcinterface

[supervisorctl]
serverurl=unix:///%project_dir%/supervisord.sock ; use a unix:// URL  for a unix socket

[program:worker]
command=/usr/bin/php %project_dir%/app/console supertag:gearman:run-worker --env=%env%
process_name=tms_worker
numprocs=1
directory=%project_dir%
autostart=true
autorestart=true
user=%user%
stdout_logfile=%root_dir%/logs/gearman-worker.info.log
stdout_logfile_maxbytes=1MB
stderr_logfile=%root_dir%/logs/gearman-worker.error.log
stderr_logfile_maxbytes=1MB
EOF;

    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Generate a supervisor config, based on current symfony2 project')
            ->addArgument('user', InputArgument::REQUIRED)
            ->setHelp(<<<EOF
The <info>%command.name%</info> command generates supervisor config to watch
over gearman workers, respawn them in case of unexpected errors:

<info>php %command.full_name% www-data</info>
<info>php %command.full_name% --env=prod www-data</info>
EOF
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $user = $input->getArgument('user');
        $project_dir = realpath($this->getContainer()->getParameter('kernel.root_dir') . '/..');
        $root_dir = realpath($this->getContainer()->getParameter('kernel.root_dir'));
        $env = $this->getContainer()->getParameter('kernel.environment');

        $replace = compact('project_dir', 'root_dir', 'user', 'env');
        $content = str_replace(
            array_map(function($key) { return '%'.$key.'%'; }, array_keys($replace)),
            array_values($replace),
            self::TEMPLATE
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
