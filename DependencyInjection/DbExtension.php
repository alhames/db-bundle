<?php

namespace DbBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * Class PgDbExtension.
 */
class DbExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');

        $dbManagerDefinition = $container->getDefinition('db.manager');

        if (!empty($config['connections'])) {
            $dbManagerDefinition->replaceArgument(1, $config['connections']);
        }

        $dbManagerDefinition->replaceArgument(2, $config['default_connection']);

        if (!empty($config['cache'])) { // todo: check
            $dbManagerDefinition->addMethodCall('setCacheItemPool', [new Reference($config['cache'])]);
        }

        if (!empty($config['logger'])) { // todo: check
            $dbManagerDefinition->addMethodCall('setLogger', [new Reference($config['logger'])]);
        }

        if (!empty($config['tables'])) {

            foreach ($config['tables'] as $alias => &$table) {
                if (null === $table['table']) {
                    $table['table'] = $alias;
                }
                if (null === $table['database']) {
                    $table['database'] = $config['default_database'];
                }
            }
            unset($table);

            $container->getDefinition('db.config')->replaceArgument(0, $config['tables']);
        }
    }
}
