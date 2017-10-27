<?php

namespace DbBundle\Tests;

use DbBundle\Db\DbConfig;
use DbBundle\Db\DbManager;
use DbBundle\DependencyInjection\DbExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Yaml\Yaml;

/**
 * Class ConfigurationTest.
 */
class ConfigurationTest extends TestCase
{
    public function testSimple()
    {
        $container = $this->load('simple');
        $this->assertTrue(($container->hasDefinition('db.manager')));
        $this->assertInstanceOf(DbManager::class, $container->get('db.manager'));
    }

    public function testConfig()
    {
        $container = $this->load('simple', 'custom');

        $this->assertSame(
            ['table' => 'table1', 'database' => 'my_database', 'connection' => null],
            $container->get('db.manager')->getConfig('table1')
        );

        $this->assertSame('`another_database`.`table_two`', $container->get('db.manager')->db('table2')->getTable());
    }


    /**
     * @param string      $globalConfig
     * @param string|null $localConfig
     *
     * @return ContainerBuilder
     *
     */
    protected function load(string $globalConfig, string $localConfig = null)
    {
        $loader = new DbExtension();
        $container = new ContainerBuilder();
        $config = [];

        $yaml = file_get_contents(__DIR__.'/Fixtures/Configuration/'.$globalConfig.'.yml');
        $config[] = Yaml::parse($yaml)['db'];
        if (null !== $localConfig) {
            $yaml = file_get_contents(__DIR__.'/Fixtures/Configuration/'.$localConfig.'.yml');
            $config[] = Yaml::parse($yaml)['db'];
        }

        $loader->load($config, $container);

        return $container;
    }
}
