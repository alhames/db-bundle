<?php

namespace DbBundle\DependencyInjection\Compiler;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
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
            $container->getDefinition('db.manager')->addMethodCall('setLogger', [new Reference($logger)]);
        }

        if (false === $container->hasExtension('web_profiler')) {
            return;
        }

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../../Resources/config'));
        $loader->load('profiler.xml');
        $container->getDefinition('db.manager')->addMethodCall('setLogger', [new Reference('db.data_collector')]);
    }
}
