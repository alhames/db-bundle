<?php

namespace Alhames\DbBundle\DependencyInjection\Compiler;

use Alhames\DbBundle\DataCollector\DbDataCollector;
use Alhames\DbBundle\Db\DbManager;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class LoggerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $logger = $container->getParameter('alhames_db.logger');
        $container->getParameterBag()->remove('alhames_db.logger');

        if (false === $logger) {
            return;
        }

        if (null !== $logger) {
            $container->getDefinition(DbManager::class)->addMethodCall('setLogger', [new Reference($logger)]);

            return;
        }

        if (false === $container->hasExtension('web_profiler')) {
            return;
        }

        $container->getDefinition(DbManager::class)->addMethodCall('setLogger', [new Reference(DbDataCollector::class)]);
    }
}
