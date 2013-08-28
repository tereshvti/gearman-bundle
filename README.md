# Gearman Bundle

Symfony2 bundle to manage and monitor PHP gearman jobs and queue.

[![Build Status](https://travis-ci.org/supertag/GearmanBundle.png?branch=master)](https://travis-ci.org/supertag/GearmanBundle)

## Features

- Support for a number of retries, which do not kill a worker, instead reschedules a job if exception was thrown.
- Supports a namespace definition for all project jobs. It will not conflict if you have two symfony2
projects deployed using gearman queue. It also simplifies a deployment process.
- Can generate you a supervisor daemon conf to ensure your worker gets respawned in case of fatal error in php.
- Has a gearman queue monitor
- Maps jobs based on Worker directory in any bundle and reads annotions for job details.

## Installation

You need to have [gearman](http://gearman.org/) installed on your box. You will also need a php extension
[PECL gearman](http://pecl.php.net/package/gearman) installed.

Install bundle through composer:

``` json
{
    "require": {
        "supertag/gearman-bundle": "dev-master"
    }
}
```

### Configuration

This will be the default configuration. If you want you can override these in `app/config.yml`

```yaml
supertag_gearman:
    servers: "localhost:4730" # gearman servers to use separated by comma, example "localhost:4730,other-domain.com:4730"
    namespace: ""             # a namespace for project jobs, will prefix all job names to prevent conflicts
```

### Register SupertagGearmanBundle in your application kernel

```php
// app/AppKernel.php
public function registerBundles()
{
    return array(
        // ...
        new Supertag\Bundle\GearmanBundle\SupertagGearmanBundle(),
        // ...
    );
}
```

If your install is successful, you should see these commands available:

    php app/console

Should contain:

![Listed commands](https://raw.github.com/supertag/GearmanBundle/master/Resources/screenshots/commands.png)

## Usage

Assuming you have **gearman** service running.
In any of your bundles you can add workers with job definitions:

``` php
<?php

namespace Supertag\Bundle\ContactBundle\Worker;

use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\Console\Output\OutputInterface;
use GearmanJob;

use Supertag\Bundle\GearmanBundle\Annotation as Gearman;

class MailingWorker extends ContainerAware
{
    /**
     * @Gearman\Job(
     *   name="supertag.mailing.send_contact_email",
     *   retries=5,
     *   description="Sends a contact email message"
     * )
     */
    public function sendContactEmail(GearmanJob $job, OutputInterface $output)
    {
        $data = json_decode($job->workload()); // assume job data is json encoded, can be serialized or be a simple string
        $output->writeLn("Attempt to send a contact message from <comment>{$data['sender_email']}</comment>");
        $message = \Swift_Message::newInstance()
            ->setSubject('A message from contact form')
            ->setFrom($data['sender_email'])
            ->setTo('info@supert.ag')
            ->setBody(
                $this->renderView(
                    'SupertagContactBundle:Email:contact.txt.twig',
                    array('body' => $data['body'])
                )
            )
        ;
        // any exception thrown will cause a retry
        $this->container->get('mailer')->send($message);
        $output->writeLn("A contact message was successfuly sent from <comment>{$data['sender_email']}</comment>");
    }
}
```

**NOTE:** worker class is inside **Worker** directory.

We have defined a simple contact message email sending job. Now we can schedule it anywhere, example:

``` php
<?php

namespace Supertag\Bundle\ContactBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class ContactController extends Controller
{
    public function contactAction()
    {
        // assuming some actions are performed to get sender data
        $data = array(
            "sender_email" => "sender@example.com",
            "body" => "Hello there !",
        );
        // schedule a low priority background job
        $this->get("supertag_gearman.client")->doLowBackground("supertag.mailing.send_contact_email", json_encode($data));
        // the action will finish without any blocking
    }
}
```

### Commands

To check all registered jobs:

    php app/console supertag:gearman:list-jobs

You should see your jobs listed similar to this:

![Screenshot of listed gearman jobs](https://raw.github.com/supertag/GearmanBundle/master/Resources/screenshots/job_list.png)

To start gearman worker, run:

    php app/console supertag:gearman:run-worker

It will listen to all registered jobs and wait for any schedule.

A screenshot of successful job processing:

![Screenshot of gearman job](https://raw.github.com/supertag/GearmanBundle/master/Resources/screenshots/normal_job.png)

A screenshot of a failing job, which fires retries and reschedules a job as low priority background job.
Any exception within a job function will cause it to retry.

![Screenshot of retried job](https://raw.github.com/supertag/GearmanBundle/master/Resources/screenshots/retries.png)

You can monitor a gearman queue:

![Screenshot - monitor queue](https://raw.github.com/supertag/GearmanBundle/master/Resources/screenshots/monitor.png)

If you wish to have a script which would ping you in case if queue gets overloaded, you could implement it based on that
command to poll status of gearman queue and check if jobs are piling up. The solution to this hardly can be abstracted,
it would be best to customize it individually.

## Run Tests

To run tests you will need to have **gearman** installed on your system.
Also you will need a **PHPUnit** 3.5 or higher.

    curl -sS https://getcomposer.org/installer | php
    php composer.phar install --dev
    phpunit


