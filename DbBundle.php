<?php

namespace DbBundle;

use DbBundle\DependencyInjection\Compiler\LoggerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Class DbBundle.
 */
class DbBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        $container->addCompilerPass(new LoggerPass());
    }
}
