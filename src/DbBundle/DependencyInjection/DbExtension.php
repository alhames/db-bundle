<?php

namespace DbBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
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

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        if (!empty($config['connections'])) {
            $container->getDefinition('db.manager')->replaceArgument(1, $config['connections']);
        }

        if (!empty($config['default_connection'])) {
            $container->getDefinition('db.manager')->replaceArgument(2, $config['default_connection']);
        }

        if (!empty($config['tables'])) {
            $container->getDefinition('db.config')->replaceArgument(0, $config['tables']);
        }
    }
}
