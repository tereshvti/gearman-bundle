# Gearman Bundle

Symfony2 bundle to manage and monitor PHP gearman jobs and queue.

[![Build Status](https://travis-ci.org/supertag/GearmanBundle.png?branch=master)](https://travis-ci.org/supertag/GearmanBundle)

## Features

- Support for a number of retries, which do not kill a worker, instead reschedules a job if exception was thrown.
- Supports a namespace definition for all project jobs. It will not conflict if you have two symfony2
projects deployed using gearman queue. It also simplifies a deployment process.
- Can generate you a supervisor daemon conf to ensure your worker gets respawned in case of fatal error in php.
- Has a gearman queue monitor
- Since php is not good with long running processes, we run jobs as console commands. It can be run manually too if
gearman is dropped.

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
In any of your bundles you can add standard symfony2 console commands, which also implements a **GearmanJobCommandInterface**.

``` php
<?php

namespace Supertag\Bundle\ContactBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Output\OutputInterface;
use Supertag\Bundle\GearmanBundle\Command\GearmanJobCommandInterface;

class SendMessageCommand extends ContainerAwareCommand implements GearmanJobCommandInterface
{
    const NAME = 'job:send-message';

    /**
     * {@inheritDoc}
     */
    public function getNumRetries()
    {
        return 5;
    }

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::NAME)
            ->setDescription('Sends a contact message')
            ->addArgument('from', InputArgument::REQUIRED, 'From user id')
            ->addArgument('template', InputArgument::REQUIRED, 'Email template')
            ->addOption('email-sender', null, InputOption::VALUE_NONE, 'Sends the same email to sender too')
            ->setHelp(<<<EOF
The <info>%command.name%</info> sends an email message

<info>php %command.full_name% 5 contact_email --email-sender</info>
EOF
        );
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        //... load the user and send a message based on input arguments
        $output->writeLn("A contact message was successfuly sent");
    }
}
```

We have defined a simple contact message email sending job. Now we can schedule it anywhere, example:

``` php
<?php

namespace Supertag\Bundle\ContactBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Supertag\Bundle\GearmanBundle\Workload;

class ContactController extends Controller
{
    public function contactAction()
    {
        // assuming some actions are performed to get sender data
        // create a workload for the job. Which is a list of parameters for the job command
        $workload = new Workload(array(5, "contact_email", "--email-sender" => null));
        // schedule a low priority background job
        $this->get("supertag_gearman.client")->doLowBackground("job:send-message", $workload);
        // the action will finish without blocking
    }
}
```

**NOTE:** Workload takes all parameters for the job command. In case if job required parameters are missing or they are
misspeled a job will note you about it. Any exceptions or php errors will trigger job to fail, proceed a retry.

In order to manage jobs which has failed all retries, see Events below.

### Events

Bundle fires few important events where you would like to hook in some cases.

- **JobBeginEvent** - fires before job execution.
- **JobFailedEvent** - triggers when job fails all retries, you might want to persist it into database for further
investigations or delayed rescheduling.
- **JobEndEvent** - triggers when job finished execution within a single try.

Listeners are created the usual way:

``` yaml
services:
    my_event_listener:
        class: AcmeBundle\EventListener\MyEventListener
        tags:
          - { name: kernel.event_listener, event: supertag_gearman.job_failed_event, method: onJobFailure }
```

### Commands

To check all registered jobs you can use a standard console command tool.

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

### Workers

To run one or more php worker processes, would recommend to use [supervisord](https://pypi.python.org/pypi/supervisor).
It will help to manage all workers - respawn ones which dies for unexpected reasons.

If easy_install is available, just run as root. Otherwise see the installation guide.

    easy_install supervisor

Generate a configuration files for **2** workers which will run as user **www-data**:

    php app/console supertag:gearman:generate-supervisor-config www-data -e prod --num-workers=2

You can now start the supervised workers:

    supervisord -c worker-supervisor.conf

It will put logs in **app/logs** directory for stderr and stdout output. Also will create a pid file in base directory
of your symfony2 project. Using that pid, you can easily kill the running worker.

#### Graceful worker stop

If you are using supervisord there is a command which can be used to stop active process.
For instance on new deployment you might probably want to kill previous release workers, because new release
might have new jobs available. You won't have troubles if the old workers will be running, since they do run commands
as separate processes.

To safely stop workers run:

    php app/console supertag:gearman:stop-worker --env=prod

In case if different worker manager is used, you can extend this command and override **doKill** method.

## Run Tests

To run tests you will need to have **gearman** installed on your system.
Also you will need a **PHPUnit** 3.5 or higher.

    curl -sS https://getcomposer.org/installer | php
    php composer.phar install --dev
    phpunit


