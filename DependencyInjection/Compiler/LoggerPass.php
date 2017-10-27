<?php

namespace DbBundle\DependencyInjection\Compiler;

use DbBundle\DataCollector\DbDataCollector;
use DbBundle\Db\DbManager;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class LoggerPass.
 */
class LoggerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $logger = $container->getParameter('db.logger');
        $container->getParameterBag()->remove('db.logger');

        if (false === $logger) {
            return;
        }

        if (null !== $logger) {
            $container->getDefinition(DbManager::class)->addMethodCall('setLogger', [new Reference($logger)]);
        }

        if (false === $container->hasExtension('web_profiler')) {
            return;
        }

        $container->getDefinition(DbManager::class)->addMethodCall('setLogger', [new Reference(DbDataCollector::class)]);
    }
}
