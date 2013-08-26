<?php

namespace Supertag\Bundle\GearmanBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Finder\Finder;
use RuntimeException, ReflectionClass, ReflectionMethod;

class ListJobsCommand extends ContainerAwareCommand
{
    const NAME = 'supertag:gearman:list-jobs';

    const GEARMAN_JOB_ANNOTATION = 'Supertag\Bundle\GearmanBundle\Annotation\Job';

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('List all gearman jobs registered in kernel')
            ->setHelp(<<<EOF
The <info>%command.name%</info> command lists and describes all gearman
jobs registered from all available bundles

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

        $jobs = array();
        foreach ($workerFiles as $workerFilename) {
            $refl = new ReflectionClass($this->readWorkerClassName($workerFilename));
            $jobs = array_merge($jobs, $this->scanWorker($refl));
        }
        if ($num = count($jobs)) {
            $output->writeLn("Found <info>{$num}</info> jobs inside project bundles, listing:\n");
        } else {
            $output->writeLn("There were no jobs found anywhere on project bundles..");
        }
        foreach ($jobs as $name => $options) {
            $output->writeLn(sprintf(
                "Job <info>%s::%s</info> named as: <comment>%s</comment> - max retries <comment>%d</comment>",
                $options['class'], $options['method'], $name, $options['retries']
            ));
            if ($options['description']) {
                $output->writeLn("    Description: <info>{$options['description']}</info>");
            }
            $output->writeLn("");
        }
    }

    /**
     * Scans worker class for jobs
     *
     * @param \ReflectionClass $worker
     * @return array
     */
    private function scanWorker(ReflectionClass $worker)
    {
        $reader = $this->getContainer()->get('annotation_reader');
        $jobs = array();
        foreach ($worker->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($job = $reader->getMethodAnnotation($method, self::GEARMAN_JOB_ANNOTATION)) {
                $jobs[$job->name] = array(
                    'description' => $job->description,
                    'retries' => intval($job->retries),
                    'class' => $worker->getName(),
                    'method' => $method->getName(),
                );
            }
        }
        return $jobs;
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
