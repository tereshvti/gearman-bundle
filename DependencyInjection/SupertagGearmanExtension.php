<?php

namespace Supertag\Bundle\GearmanBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Config\Definition\Processor;

class SupertagGearmanExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yml');

        $processor = new Processor;
        $configuration = new Configuration;
        $config = $processor->processConfiguration($configuration, $configs);

        $container->setParameter('supertag_gearman.servers', $config['servers']);
        $container->setParameter('supertag_gearman.namespace', $config['namespace']);
    }
}
