<?php

namespace Supertag\Bundle\GearmanBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputOption;
use TweeGearmanStat\Queue\Gearman as GearmanTelnetMonitor;

/**
 * Sources are mainly authored by <https://github.com/hautelook/GearmanBundle>
 */
class MonitorCommand extends ContainerAwareCommand
{
    const NAME = 'supertag:gearman:monitor';

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->addOption('watch', null, InputOption::VALUE_REQUIRED, 'Check for changes every n seconds set in option or one by default')
            ->setDescription('Run monitor on gearman queue')
            ->setHelp(<<<EOF
The <info>%command.name%</info> command monitors gearman queue

<info>php %command.full_name%</info>
<info>php %command.full_name% --watch=1 --env=prod</info>
EOF
        );
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $adapter = new GearmanTelnetMonitor($this->getGearmanServers());
        $watch = $input->getOption('watch');
        $once = true;
        while ($once || $watch) {
            $status = $adapter->status();
            foreach ($status as $server => $queues) {
                $output->writeln("<info>Status for Server {$server}</info>");
                $output->writeln("");

                if ($this->getHelperSet()->has('table')) {
                    // Symfony 2.3 console goodness
                    /** @var $table \Symfony\Component\Console\Helper\TableHelper */
                    $table = $this->getHelperSet()->get('table');
                    $table
                        ->setHeaders(array('Queue', 'Jobs', 'Workers working', 'Workers total'))
                        ->setRows($queues);

                    $table->render($output);
                } else {
                    foreach ($queues as $queue) {
                        $str = "    <comment>{$queue['name']}</comment> Jobs: {$queue['queue']}";
                        $str .= " Workers: {$queue['running']} / {$queue['workers']}";
                        $output->writeln($str);
                    }
                }
            }
            $once = false;
            if ($watch) {
                sleep(intval($watch));
            }
        }
    }

    /**
     * Formats servers as argument for GearmanTelnetMonitor
     *
     * @return array
     */
    private function getGearmanServers()
    {
        $servers = array_map('trim', explode(',', $this->getContainer()->getParameter('supertag_gearman.servers')));
        $return = array();
        foreach ($servers as $i => $server) {
            $parts = explode(':', $server);
            if (count($parts) === 1) {
                array_push($parts, '4730');
            }
            list($host, $port) = $parts;
            $return['server' . ($i + 1)] = array(
                'host' => $host,
                'port' => intval($port),
                'timeout' => 1
            );
        }
        return $return;
    }
}
