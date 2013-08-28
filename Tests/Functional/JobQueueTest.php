<?php

namespace Supertag\Bundle\GearmanBundle\Tests\Functional;

class JobQueueTest extends \PHPUnit_Framework_TestCase
{
    private $logFile, $pid;

    protected function setUp()
    {
        $appDir = realpath(__DIR__ . '/../sf2app/app');
        $this->logFile = $appDir . '/logs/gearman-worker.log';
        // make sure log directory is created
        if (!is_dir($appDir . '/logs')) {
            @mkdir($appDir . '/logs');
        }
        // run gearman worker in the background
        exec($cmd = sprintf("%s/console supertag:gearman:run-worker --env=test > %s &", $appDir, $this->logFile));
        // get a pid
        exec("ps aux | grep 'supertag:gearman:run-worker' | grep -v grep | awk '{ print $2 }' | head -1", $op);
        $this->pid = $op[0];
    }

    protected function tearDown()
    {
        exec("kill {$this->pid}");
        @unlink($this->logFile);
        if (file_exists($appLogFile = dirname($this->logFile) . '/app.log')) {
            @unlink($appLogFile);
        }
    }

    /**
     * @test
     */
    function shouldRunScheduledJob()
    {
        $this->assertReceivedLogMessage($this->logFile, "Registering job: Acme\Bundle\ApiBundle\Worker\SomeWorker::myGearmanJob as normal.gearman.job");

        $gmc = $this->createGearmanClient();
        $gmc->doBackground('normal.gearman.job', 'work');

        $this->assertReceivedLogMessage($this->logFile, "Successfully finished normal.gearman.job");
    }

    /**
     * @test
     */
    function shouldRetryAFailingJobAndFireJobFailedEvent()
    {
        $this->assertReceivedLogMessage($this->logFile, "Registering job: Acme\ContactBundle\Worker\FailingWorker::myFailingGearmanJob as failing.gearman.job");

        $gmc = $this->createGearmanClient();
        $gmc->doBackground('failing.gearman.job', 'work');

        $this->assertReceivedLogMessage($this->logFile, "[Job failing.gearman.job] - failed when processing: work. Reason is: ups I failed");
        $this->assertReceivedLogMessage($this->logFile, "Number of retries left: 4");
        $this->assertReceivedLogMessage($this->logFile, "Number of retries left: 3");
        $this->assertReceivedLogMessage($this->logFile, "Number of retries left: 2");
        $this->assertReceivedLogMessage($this->logFile, "Number of retries left: 1");
        $this->assertReceivedLogMessage($this->logFile, "Number of retries left: 0");

        $appLogFile = dirname($this->logFile) . '/app.log';
        $this->assertReceivedLogMessage($appLogFile, "Job failing.gearman.job has failed, while processing: work");
    }

    private function createGearmanClient()
    {
        $gmc = new \GearmanClient;
        $gmc->addServer('127.0.0.1', 4730);
        return $gmc;
    }

    private function assertReceivedLogMessage($file, $msg)
    {
        $retries = 30; // travis might be slow, wait max 30 seconds
        do {
            $result = stripos(file_get_contents($file), $msg) !== false;
        } while (!$result && --$retries && sleep(1) !== false);
        $this->assertTrue($result, "Expected message: '{$msg}' was never received from gearman process");
    }
}
