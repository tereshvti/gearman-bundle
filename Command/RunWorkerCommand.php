<?php

namespace Supertag\Bundle\GearmanBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\ParameterBag;
use GearmanWorker;
use GearmanJob;
use RuntimeException, ReflectionClass, ReflectionMethod;

class RunWorkerCommand extends ContainerAwareCommand
{
    const NAME = 'supertag:gearman:run-worker';

    const GEARMAN_JOB_ANNOTATION = 'Supertag\Bundle\GearmanBundle\Annotation\Job';

    /**
     * All collected gearman jobs
     *
     * @var array
     */
    private $jobs = array();

    /**
     * List of retries based on job hash
     *
     * @var \Symfony\Component\HttpFoundation\ParameterBag
     */
    private $retries;

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Run gearman workers from all bundles')
            ->setHelp(<<<EOF
The <info>%command.name%</info> command registers all gearman workers for all
kernel bundles based on their directory. Example a worker location is detected like:
<comment>MyVendorName/BundleName/Worker/*.php</comment>

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
        $workerFiles = array();
        foreach ($this->getContainer()->get('kernel')->getBundles() as $bundle) {
            if (is_dir($workerDir = $bundle->getPath().'/Worker')) {
                $finder = new Finder;
                $finder->files()->in($workerDir)->name('*.php');
                foreach ($finder as $workerFile) {
                    $workerFiles[] = $workerFile->getRealPath();
                }
            }
        }
        if (empty($workerFiles)) {
            throw new RuntimeException("Could not find any workers in any of registered bundles..");
        }

        $this->retries = new ParameterBag;
        $gmworker = new GearmanWorker;
        $gmworker->addServers($this->getContainer()->getParameter('supertag_gearman.servers'));

        foreach ($workerFiles as $workerFilename) {
            $refl = new ReflectionClass($this->readWorkerClassName($workerFilename));
            $this->registerWorker($refl, $gmworker, $output);
        }

        while ($gmworker->work()) {}
    }

    /**
     * Scans worker class for jobs
     *
     * @param \ReflectionClass $worker
     * @param GearmanWorker $gmw
     * @param OutputInterface $output
     * @throws RuntimeException - if job name is already registered
     */
    private function registerWorker(ReflectionClass $worker, GearmanWorker $gmw, OutputInterface $output)
    {
        $reader = $this->getContainer()->get('annotation_reader');
        $workerInst = null;
        foreach ($worker->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($job = $reader->getMethodAnnotation($method, self::GEARMAN_JOB_ANNOTATION)) {
                if (isset($this->jobs[$job->name])) {
                    throw new RuntimeException("Job: {$job->name} was already registered in worker: {$this->jobs[$job->name]['class']}");
                }
                $this->jobs[$job->name] = array(
                    'description' => $job->description,
                    'retries' => intval($job->retries),
                    'class' => $worker->getName(),
                    'method' => $method->getName(),
                );
                if (null === $workerInst) {
                    $workerInst = $worker->newInstance();
                    if ($worker->implementsInterface('Symfony\Component\DependencyInjection\ContainerAwareInterface')) {
                        $workerInst->setContainer($this->getContainer());
                    }
                }
                $this->registerWorkerJob($workerInst, $job->name, $gmw, $output);
            }
        }
    }

    /**
     * Registers a gearman job. Implements retry mechanism
     * when job throws an exception. Otherwise it would force
     * this command to exit.
     *
     * @param object $worker - worker class instance
     * @param string $name - job name
     * @param GearmanWorker $gmw
     * @param OutputInterface $output
     */
    private function registerWorkerJob($worker, $name, GearmanWorker $gmw, OutputInterface $output)
    {
        $job = $this->jobs[$name];
        $gmc = $this->getContainer()->get('supertag_gearman.client');
        $prefixedJobName = $gmc->getJobName($name);
        $retries = $this->retries;

        $output->writeLn("Registering job: <info>{$job['class']}::{$job['method']}</info> as <comment>{$name}</comment>");

        $gmw->addFunction($prefixedJobName, function(GearmanJob $gmj) use ($job, $name, $gmc, $output, $worker, $retries) {
            $result = null;
            $hash = sha1($name.$gmj->workload());
            try {
                $result = call_user_func_array(array($worker, $job['method']), array($gmj, $output));
                $retries->has($hash) && $retries->remove($hash);
            } catch (\Exception $e) {
                $msg = "<error>[Job {$name}]</error> - failed when processing: </info>" . $gmj->workload()."</info>. ";
                $msg .= "Reason is: <comment>" . $e->getMessage()."</comment>. ";
                $gmj->sendFail();
                // for retries we use a specific hash to determine how many retries were
                // applied already. hash is generated from {jobName}{workload} sha-1
                $numRetriesLeft = $retries->has($hash) ? $retries->get($hash) : $job['retries'];
                $msg .= "Number of retries left: <info>".($numRetriesLeft - 1)."</info>";
                $output->writeLn($msg);
                if ($numRetriesLeft > 1) {
                    $retries->set($hash, $numRetriesLeft - 1);
                    // reschedule job, always in low priority background
                    $gmc->doLowBackground($name, $gmj->workload());
                } else {
                    $retries->remove($hash);
                    // send to the database
                }
                return false;
            }
            $gmj->sendComplete($result);
            return true;
        });
    }

    /**
     * Reads tokenized php file and extracts class name
     *
     * @param string $filename
     * @return string
     * @throws RuntimeException - if fails to find a class name
     */
    private function readWorkerClassName($filename)
    {
        $codeTokens = token_get_all(file_get_contents($filename));
        foreach ($codeTokens as $codeTokenIndex => $codeTokenValue) {
            if (is_array($codeTokenValue)) {
                list($codeTokenType, $codeTokenContent) = $codeTokenValue;
                if (!isset($className) && T_CLASS === $codeTokenType) {
                    $className = $codeTokens[$codeTokenIndex + 2][1];
                }
                if (!isset($namespace) && T_NAMESPACE === $codeTokenType) {
                    $namespace = '';
                    for ($i = 2; true; ++ $i) {
                        $namespaceToken = $codeTokens[$codeTokenIndex + $i];
                        if (is_array($namespaceToken)) {
                            $namespace .= $namespaceToken[1];
                        }
                        if (is_string($namespaceToken)) {
                            break;
                        }
                    }
                }
            }
            if (isset($className) && isset($namespace)) {
                return implode('\\', array($namespace, $className));
            }
        }

        throw new RuntimeException("Class name could not be determined for worker file: {$filename}");
    }
}
