<?php

namespace DbBundle\DependencyInjection;

use DbBundle\Db\DbConfig;
use DbBundle\Db\DbManager;
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

        $dbManagerDefinition = $container->getDefinition(DbManager::class);

        if (!empty($config['connections'])) {
            $dbManagerDefinition->setArgument('$config', $config['connections']);
        }

        $dbManagerDefinition->setArgument('$defaultConnection', $config['default_connection']);

        if (!empty($config['cache'])) {
            $dbManagerDefinition->addMethodCall('setCacheItemPool', [new Reference($config['cache'])]);
        }

        if (!empty($config['query_formatter'])) {
            $dbManagerDefinition->addMethodCall('setQueryFormatter', [new Reference($config['query_formatter'])]);
        }

        $container->setParameter('db.logger', $config['logger']);

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

            $container->getDefinition(DbConfig::class)->setArgument('$tables', $config['tables']);
        }
    }
}
