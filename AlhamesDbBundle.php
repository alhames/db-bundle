<?php

namespace Alhames\DbBundle;

use Alhames\DbBundle\DependencyInjection\Compiler\LoggerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Class DbBundle.
 */
class AlhamesDbBundle extends Bundle
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
