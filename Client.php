<?php

namespace Supertag\Bundle\GearmanBundle;

use GearmanClient;

/**
 * A proxy for gearman client, we might need a namespace
 * for every job name
 */
class Client
{
    private $gmc;
    private $servers;
    private $namespace;

    public function __construct($servers, $namespace)
    {
        $this->servers = $servers;
        $this->namespace = $namespace;
    }

    private function connect()
    {
        if (null === $this->gmc) {
            $this->gmc = new GearmanClient;
            $this->gmc->addServers($this->servers);
        }
    }

    /**
     * Get the php extension gearman client
     *
     * @return GearmanClient
     */
    public function getGearmanClient()
    {
        $this->connect();
        return $this->gmc;
    }

    /**
     * Get job $name prefixed with namespace
     *
     * @param string $name
     * @return string
     */
    public function getJobName($name)
    {
        return $this->namespace . $name;
    }

    /**
     * Schedules a background job
     *
     * @param string $jobName - the name of job to do. Will append st_version to prevent conflict
     * @param Workload $workload - command parameters for the job command
     * @return Resource - job handle
     */
    public function doBackground($jobName, Workload $workload)
    {
        $this->connect();
        return $this->gmc->doBackground($this->getJobName($jobName), (string)$workload);
    }

    /**
     * Schedules a high priority background job
     *
     * @param string $jobName - the name of job to do. Will append st_version to prevent conflict
     * @param Workload $workload - command parameters for the job command
     * @return Resource - job handle
     */
    public function doHighBackground($jobName, Workload $workload)
    {
        $this->connect();
        return $this->gmc->doHighBackground($this->getJobName($jobName), (string)$workload);
    }

    /**
     * Schedules a low priority background job
     *
     * @param string $jobName - the name of job to do. Will append st_version to prevent conflict
     * @param Workload $workload - command parameters for the job command
     * @return Resource - job handle
     */
    public function doLowBackground($jobName, Workload $workload)
    {
        $this->connect();
        return $this->gmc->doLowBackground($this->getJobName($jobName), (string)$workload);
    }

    /**
     * Schedules a normal priority job
     *
     * @param string $jobName - the name of job to do. Will append st_version to prevent conflict
     * @param Workload $workload - command parameters for the job command
     * @return string - status result
     */
    public function doNormal($jobName, Workload $workload)
    {
        $this->connect();
        return $this->gmc->doNormal($this->getJobName($jobName), (string)$workload);
    }

    /**
     * Schedules a high priority job
     *
     * @param string $jobName - the name of job to do. Will append st_version to prevent conflict
     * @param Workload $workload - command parameters for the job command
     * @return string - status result
     */
    public function doHigh($jobName, Workload $workload)
    {
        $this->connect();
        return $this->gmc->doHigh($this->getJobName($jobName), (string)$workload);
    }

    /**
     * Schedules a low priority job
     *
     * @param string $jobName - the name of job to do. Will append st_version to prevent conflict
     * @param Workload $workload - command parameters for the job command
     * @return string - status result
     */
    public function doLow($jobName, Workload $workload)
    {
        $this->connect();
        return $this->gmc->doLow($this->getJobName($jobName), (string)$workload);
    }

    /**
     * Add a normal priority task
     *
     * @param string $jobName - the name of job to do. Will append st_version to prevent conflict
     * @param Workload $workload - command parameters for the job command
     * @param mixed $context
     * @param string $unique - unique task identifier
     * @param boolean $background - whether to run in background or not
     * @return GearmanTask
     */
    public function addTask($jobName, Workload $workload, &$context, $unique, $background = true)
    {
        $this->connect();
        $method = 'addTask' . $background ? 'Background' : '';
        return $this->gmc->{$method}($this->getJobName($jobName), (string)$workload, $context, $unique);
    }

    /**
     * Add a high priority task
     *
     * @param string $jobName - the name of job to do. Will append st_version to prevent conflict
     * @param Workload $workload - command parameters for the job command
     * @param mixed $context
     * @param string $unique - unique task identifier
     * @param boolean $background - whether to run in background or not
     * @return GearmanTask
     */
    public function addTaskHigh($jobName, Workload $workload, &$context, $unique, $background = true)
    {
        $this->connect();
        $method = 'addTaskHigh' . $background ? 'Background' : '';
        return $this->gmc->{$method}($this->getJobName($jobName), (string)$workload, $context, $unique);
    }

    /**
     * Add a low priority task
     *
     * @param string $jobName - the name of job to do. Will append st_version to prevent conflict
     * @param Workload $workload - command parameters for the job command
     * @param mixed $context
     * @param string $unique - unique task identifier
     * @param boolean $background - whether to run in background or not
     * @return GearmanTask
     */
    public function addTaskLow($jobName, Workload $workload, &$context, $unique, $background = true)
    {
        $this->connect();
        $method = 'addTaskLow' . $background ? 'Background' : '';
        return $this->gmc->{$method}($this->getJobName($jobName), (string)$workload, $context, $unique);
    }

    /**
     * Run added tasks
     *
     * @return boolean
     */
    public function runTasks()
    {
        $this->connect();
        return $this->gmc->runTasks();
    }
}
