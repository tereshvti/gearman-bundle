<?php

namespace Supertag\Bundle\GearmanBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder();

        $builder->root('supertag_gearman')
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode('servers')->defaultValue('localhost:4730')->end()
                ->scalarNode('namespace')->defaultValue('')->end()
            ->end()
        ;
        return $builder;
    }
}
