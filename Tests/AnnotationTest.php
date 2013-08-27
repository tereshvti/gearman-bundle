<?php

namespace Supertag\Bundle\GearmanBundle\Tests;

use Doctrine\Common\Annotations\AnnotationReader;
use Supertag\Bundle\GearmanBundle\Command\RunWorkerCommand;

class AnnotationTest extends \PHPUnit_Framework_TestCase
{
    private $reader;

    protected function setUp()
    {
        $this->reader = new AnnotationReader;
    }

    /**
     * @test
     */
    function shouldBeAbleToReadWorkerAnnotations()
    {
        $method = new \ReflectionMethod("Acme\ContactBundle\Worker\FailingWorker", "myFailingGearmanJob");
        $job = $this->reader->getMethodAnnotation($method, RunWorkerCommand::GEARMAN_JOB_ANNOTATION);
        $this->assertNotNull($job, "Job Anotation should be found");

        $this->assertSame("failing.gearman.job", $job->name, "Job name does not match");
        $this->assertSame("Describe failing gearman job", $job->description, "Job description does not match");
        $this->assertSame(5, intval($job->retries), "Number of retries does not match");
    }

    /**
     * @test
     */
    function shouldBeAbleToReadWorkerAnnotationsFromAnother()
    {
        $method = new \ReflectionMethod("Acme\Bundle\ApiBundle\Worker\SomeWorker", "myGearmanJob");
        $job = $this->reader->getMethodAnnotation($method, RunWorkerCommand::GEARMAN_JOB_ANNOTATION);
        $this->assertNotNull($job, "Job Anotation should be found");

        $this->assertSame("normal.gearman.job", $job->name, "Job name does not match");
        $this->assertSame("Describe normal gearman job", $job->description, "Job description does not match");
        $this->assertSame(3, intval($job->retries), "Number of retries does not match");
    }
}
