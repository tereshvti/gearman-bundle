<?php

namespace Supertag\Bundle\GearmanBundle\Tests\Functional;

use Supertag\Bundle\GearmanBundle\Workload;

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
        $this->assertReceivedLogMessage($this->logFile, "Registering job: job:deploy-project");

        $gmc = $this->createGearmanClient();
        $gmc->doBackground('job:deploy-project', new Workload(array(
            '--force' => null,
            '5'
        )));

        $this->assertReceivedLogMessage($this->logFile, "Successfully finished project deploy");
    }

    /**
     * @test
     */
    function shouldFailWhenJobInputIsNotValid()
    {
        $this->assertReceivedLogMessage($this->logFile, "Registering job: job:deploy-project");

        $gmc = $this->createGearmanClient();
        $gmc->doBackground('job:deploy-project', new Workload);

        $this->assertReceivedLogMessage($this->logFile, "Failed: Not enough arguments.: . Number of retries left: 4");
        $this->assertReceivedLogMessage($this->logFile, "Failed: Not enough arguments.: . Number of retries left: 3");
        $this->assertReceivedLogMessage($this->logFile, "Failed: Not enough arguments.: . Number of retries left: 2");
        $this->assertReceivedLogMessage($this->logFile, "Failed: Not enough arguments.: . Number of retries left: 1");
        $this->assertReceivedLogMessage($this->logFile, "Failed: Not enough arguments.: . Number of retries left: 0");
    }

    /**
     * @test
     */
    function shouldRetryAFailingJobAndFireJobFailedEvent()
    {
        $this->assertReceivedLogMessage($this->logFile, "Registering job: job:failing");

        $gmc = $this->createGearmanClient();
        $gmc->doBackground('job:failing', new Workload);

        $this->assertReceivedLogMessage($this->logFile, "Failed: Failed while processing..: job:failing --env=test");
        $this->assertReceivedLogMessage($this->logFile, "Number of retries left: 1");
        $this->assertReceivedLogMessage($this->logFile, "Number of retries left: 0");

        $appLogFile = dirname($this->logFile) . '/app.log';
        // test event
        $this->assertReceivedLogMessage($appLogFile, "Event: Job job:failing has failed");
    }

    private function createGearmanClient()
    {
        $gmc = new \GearmanClient;
        $gmc->addServer('127.0.0.1', 4730);
        return $gmc;
    }

    private function assertReceivedLogMessage($file, $msg)
    {
        $retries = 10; // travis might be slow, wait max 10 seconds
        $content = null;
        do {
            $result = stripos($content = file_get_contents($file), $msg) !== false;
        } while (!$result && --$retries && sleep(1) !== false);
        $this->assertTrue($result, "Expected message: '{$msg}' was never received from gearman process, output was: ".$content);
    }
}
