<?php

use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Config\Loader\LoaderInterface;

class AppKernel extends Kernel
{
    public function registerBundles()
    {
        return array(
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle, // will reuse kernel event dispatcher
            new Symfony\Bundle\MonologBundle\MonologBundle, // will use to log events
            new Acme\ContactBundle\AcmeContactBundle,
            new Acme\Bundle\ApiBundle\AcmeApiBundle,
            new Supertag\Bundle\GearmanBundle\SupertagGearmanBundle,
        );
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(__DIR__.'/config.yml');
    }
}
