<?php

namespace Supertag\Bundle\GearmanBundle\Tests;

use Supertag\Bundle\GearmanBundle\Client;

class GearmanClientTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    function shouldNamespaceAJobName()
    {
        $client = new Client("", "");
        $this->assertSame('no_namespace', $client->getJobName('no_namespace'));

        $client = new Client("", "ns_");
        $this->assertSame('ns_no_namespace', $client->getJobName('no_namespace'));
    }
}
