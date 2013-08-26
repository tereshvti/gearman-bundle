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
    private $namespace;

    public function __construct(GearmanClient $gmc, $namespace)
    {
        $this->gmc = $gmc;
        $this->namespace = $namespace;
    }

    /**
     * Get the php extension gearman client
     *
     * @return GearmanClient
     */
    public function getGearmanClient()
    {
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
     * @param string $workload - json encoded, serialized or simple string data for the job
     * @return Resource - job handle
     */
    public function doBackground($jobName, $workload)
    {
        return $this->gmc->doBackground($this->getJobName($jobName), $workload);
    }

    /**
     * Schedules a high priority background job
     *
     * @param string $jobName - the name of job to do. Will append st_version to prevent conflict
     * @param string $workload - json encoded, serialized or simple string data for the job
     * @return Resource - job handle
     */
    public function doHighBackground($jobName, $workload)
    {
        return $this->gmc->doHighBackground($this->getJobName($jobName), $workload);
    }

    /**
     * Schedules a low priority background job
     *
     * @param string $jobName - the name of job to do. Will append st_version to prevent conflict
     * @param string $workload - json encoded, serialized or simple string data for the job
     * @return Resource - job handle
     */
    public function doLowBackground($jobName, $workload)
    {
        return $this->gmc->doLowBackground($this->getJobName($jobName), $workload);
    }

    /**
     * Schedules a normal priority job
     *
     * @param string $jobName - the name of job to do. Will append st_version to prevent conflict
     * @param string $workload - json encoded, serialized or simple string data for the job
     * @return string - status result
     */
    public function doNormal($jobName, $workload)
    {
        return $this->gmc->doNormal($this->getJobName($jobName), $workload);
    }

    /**
     * Schedules a high priority job
     *
     * @param string $jobName - the name of job to do. Will append st_version to prevent conflict
     * @param string $workload - json encoded, serialized or simple string data for the job
     * @return string - status result
     */
    public function doHigh($jobName, $workload)
    {
        return $this->gmc->doHigh($this->getJobName($jobName), $workload);
    }

    /**
     * Schedules a low priority job
     *
     * @param string $jobName - the name of job to do. Will append st_version to prevent conflict
     * @param string $workload - json encoded, serialized or simple string data for the job
     * @return string - status result
     */
    public function doLow($jobName, $workload)
    {
        return $this->gmc->doLow($this->getJobName($jobName), $workload);
    }

    /**
     * Add a normal priority task
     *
     * @param string $jobName - the name of job to do. Will append st_version to prevent conflict
     * @param string $workload - json encoded, serialized or simple string data for the job
     * @param mixed $context
     * @param string $unique - unique task identifier
     * @param boolean $background - whether to run in background or not
     * @return GearmanTask
     */
    public function addTask($jobName, $workload, &$context, $unique, $background = true)
    {
        $method = 'addTask' . $background ? 'Background' : '';
        return $this->gmc->{$method}($this->getJobName($jobName), $workload, $context, $unique);
    }

    /**
     * Add a high priority task
     *
     * @param string $jobName - the name of job to do. Will append st_version to prevent conflict
     * @param string $workload - json encoded, serialized or simple string data for the job
     * @param mixed $context
     * @param string $unique - unique task identifier
     * @param boolean $background - whether to run in background or not
     * @return GearmanTask
     */
    public function addTaskHigh($jobName, $workload, &$context, $unique, $background = true)
    {
        $method = 'addTaskHigh' . $background ? 'Background' : '';
        return $this->gmc->{$method}($this->getJobName($jobName), $workload, $context, $unique);
    }

    /**
     * Add a low priority task
     *
     * @param string $jobName - the name of job to do. Will append st_version to prevent conflict
     * @param string $workload - json encoded, serialized or simple string data for the job
     * @param mixed $context
     * @param string $unique - unique task identifier
     * @param boolean $background - whether to run in background or not
     * @return GearmanTask
     */
    public function addTaskLow($jobName, $workload, &$context, $unique, $background = true)
    {
        $method = 'addTaskLow' . $background ? 'Background' : '';
        return $this->gmc->{$method}($this->getJobName($jobName), $workload, $context, $unique);
    }

    /**
     * Run added tasks
     *
     * @return boolean
     */
    public function runTasks()
    {
        return $this->gmc->runTasks();
    }
}
