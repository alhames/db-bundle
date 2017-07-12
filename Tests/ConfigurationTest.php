<?php

namespace DbBundle\Tests;

use DbBundle\DependencyInjection\Configuration;
use DbBundle\DependencyInjection\DbExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Class ConfigurationTest.
 */
class ConfigurationTest extends TestCase
{
    /** @var array  */
    protected static $defaultConfig = [
        'default_connection' => 'default',
        'default_database' => null,
    ];

    /**
     * Some basic tests to make sure the configuration is correctly processed in
     * the standard case.
     */
    public function testProcessSimpleCase()
    {
        $configs = [static::$defaultConfig];
        $config = $this->process($configs);

        $this->assertArrayHasKey('default_connection', $config);
    }

    public function testExtension()
    {
        $loader = new DbExtension();
        $container = new ContainerBuilder();
        $loader->load([static::$defaultConfig], $container);

        $this->assertTrue(($container->hasDefinition('db.config')));
        $this->assertTrue(($container->hasDefinition('db.manager')));
    }

    /**
     * Processes an array of configurations and returns a compiled version.
     *
     * @param array $configs An array of raw configurations
     *
     * @return array A normalized array
     */
    protected function process($configs)
    {
        $processor = new Processor();

        return $processor->processConfiguration(new Configuration(), $configs);
    }
}
