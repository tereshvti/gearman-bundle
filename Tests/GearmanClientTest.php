<?php

namespace Supertag\Bundle\GearmanBundle\Tests;

use Doctrine\Common\Annotations\AnnotationReader;
use Supertag\Bundle\GearmanBundle\Client;

class GearmanClientTest extends \PHPUnit_Framework_TestCase
{
    private $gearmanClient;

    protected function setUp()
    {
        $this->gearmanClient = $this->getMock("GearmanClient");
    }

    /**
     * @test
     */
    function shouldNamespaceAJobName()
    {
        $client = new Client($this->gearmanClient, "");
        $this->assertSame('no_namespace', $client->getJobName('no_namespace'));

        $client = new Client($this->gearmanClient, "ns_");
        $this->assertSame('ns_no_namespace', $client->getJobName('no_namespace'));
    }

    /**
     * @test
     */
    function shouldProxyANamespacedJobCall()
    {
        $this->gearmanClient
            ->expects($this->once())
            ->method("doBackground")
            ->with($this->equalTo('namespaced_job_name'), $this->equalTo('work'));

        $client = new Client($this->gearmanClient, "namespaced_");
        $client->doBackground('job_name', 'work');
    }
}
