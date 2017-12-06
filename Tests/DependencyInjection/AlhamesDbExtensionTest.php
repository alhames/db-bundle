<?php

namespace DbBundle\Tests\DependencyInjection;

use Alhames\DbBundle\DataCollector\DbDataCollector;
use Alhames\DbBundle\Db\DbConfig;
use Alhames\DbBundle\Db\DbManager;
use Alhames\DbBundle\DependencyInjection\AlhamesDbExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Yaml\Yaml;

/**
 * Class DbExtensionTest.
 */
class AlhamesDbExtensionTest extends TestCase
{
    public function testConfig()
    {
        $container = new ContainerBuilder();
        $loader = new AlhamesDbExtension();
        $loader->load([$this->getConfig()], $container);
        $this->assertTrue($container instanceof ContainerBuilder);

        $this->assertTrue($container->hasDefinition(DbManager::class));
        $this->assertSame(DbManager::class, (string) $container->getAlias('alhames_db.manager'));
        $this->assertTrue($container->hasDefinition(DbConfig::class));
        $this->assertSame(DbConfig::class, (string) $container->getAlias('alhames_db.config'));
        $this->assertTrue($container->hasDefinition(DbDataCollector::class));
    }

    /**
     * @param string $name
     *
     * @return array
     */
    protected function getConfig(string $name = 'default'): array
    {
        $yaml = file_get_contents(__DIR__.'/../Fixtures/config/'.$name.'.yml');

        return Yaml::parse($yaml)['alhames_db'];
    }
}
