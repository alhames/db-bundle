<?php

namespace Alhames\DbBundle\DependencyInjection;

use Alhames\DbBundle\Db\DbConfig;
use Alhames\DbBundle\Db\DbManager;
use Alhames\DbBundle\Db\DbManagerAwareInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * Class PgDbExtension.
 */
class AlhamesDbExtension extends Extension
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

        $dbmDefinition = $container->getDefinition(DbManager::class);
        $dbmDefinition->setArgument('$config', $config['connections'] ?? []);
        $dbmDefinition->setArgument('$defaultConnection', $config['default_connection']);

        if (!empty($config['cache'])) {
            $dbmDefinition->setArgument('$cacheItemPool', new Reference($config['cache']));
        }

        if (!empty($config['query_formatter'])) {
            $dbmDefinition->setArgument('$queryFormatter', new Reference($config['query_formatter']));
        }

        $container->setParameter('alhames_db.logger', $config['logger']);

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

        $container->registerForAutoconfiguration(DbManagerAwareInterface::class)
            ->addMethodCall('setDbManager', [new Reference(DbManager::class)]);
    }
}
