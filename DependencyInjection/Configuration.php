<?php

namespace DbBundle\DependencyInjection;

use DbBundle\DbBundle;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Class Configuration.
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('db');

        $rootNode
            ->children()
                ->scalarNode('default_connection')
                    ->isRequired()
                    ->defaultValue('default')
                ->end()
                ->scalarNode('default_database')
                    ->isRequired()
                    ->defaultNull()
                ->end()
                ->scalarNode('cache')
                    ->defaultNull()
                ->end()
                ->scalarNode('logger')
                    ->defaultNull()
                ->end()

                ->arrayNode('connections')
                    ->useAttributeAsKey('alias')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('host')->defaultValue('127.0.0.1')->end()
                            ->scalarNode('username')->defaultValue('root')->end()
                            ->scalarNode('password')->defaultValue('')->end()
                            ->scalarNode('database')->defaultNull()->end()
                            ->integerNode('port')->defaultValue(3306)->end()
                            ->scalarNode('charset')->defaultValue('utf8mb4')->end()
                        ->end()
                    ->end()
                ->end()

                ->arrayNode('tables')
                    ->useAttributeAsKey('alias')
                    ->prototype('array')
                        ->treatNullLike([])
                        ->children()
                            ->scalarNode('table')->defaultNull()->end()
                            ->scalarNode('database')->defaultNull()->end()
                            ->scalarNode('connection')->defaultNull()->end()
                        ->end()
                    ->end()
                ->end()

            ->end()
        ;

        return $treeBuilder;
    }
}