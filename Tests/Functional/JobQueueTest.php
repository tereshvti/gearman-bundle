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
    }

    /**
     * @test
     */
    function shouldRunScheduledJob()
    {
        $this->assertReceivedGearmanMessage("Registering job: Acme\Bundle\ApiBundle\Worker\SomeWorker::myGearmanJob as normal.gearman.job");

        $gmc = $this->createGearmanClient();
        $gmc->doBackground('normal.gearman.job', 'work');

        $this->assertReceivedGearmanMessage("Successfully finished normal.gearman.job");
    }

    /**
     * @test
     */
    function shouldRetryAFailingJob()
    {
        $this->assertReceivedGearmanMessage("Registering job: Acme\ContactBundle\Worker\FailingWorker::myFailingGearmanJob as failing.gearman.job");

        $gmc = $this->createGearmanClient();
        $gmc->doBackground('failing.gearman.job', 'work');

        $this->assertReceivedGearmanMessage("[Job failing.gearman.job] - failed when processing: work. Reason is: ups I failed");
        $this->assertReceivedGearmanMessage("Number of retries left: 4");
        $this->assertReceivedGearmanMessage("Number of retries left: 3");
        $this->assertReceivedGearmanMessage("Number of retries left: 2");
        $this->assertReceivedGearmanMessage("Number of retries left: 1");
        $this->assertReceivedGearmanMessage("Number of retries left: 0");
    }

    private function createGearmanClient()
    {
        $gmc = new \GearmanClient;
        $gmc->addServer('127.0.0.1', 4730);
        return $gmc;
    }

    private function assertReceivedGearmanMessage($msg)
    {
        $retries = 30; // travis might be slow, wait max 30 seconds
        do {
            $result = stripos(file_get_contents($this->logFile), $msg) !== false;
        } while (!$result && --$retries && sleep(1) !== false);
        $this->assertTrue($result, "Expected message: '{$msg}' was never received from gearman process");
    }
}
