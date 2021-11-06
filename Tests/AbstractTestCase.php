<?php
declare(strict_types=1);

namespace Alhames\DbBundle\Tests;

use Alhames\DbBundle\Db\DbConfig;
use Alhames\DbBundle\Db\DbManager;
use Alhames\DbBundle\Db\DbQuery;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

abstract class AbstractTestCase extends TestCase
{
    protected DbManager $dbm;

    protected function setUp(): void
    {
        $config = $this->getConfig('test');
        $config['connections']['test'] = [
            'host' => $_SERVER['DB_HOST'] ?? '127.0.0.1',
            'username' => $_SERVER['DB_USER'] ?? 'root',
            'password' => $_SERVER['DB_PASS'] ?? '',
            'database' => $_SERVER['DB_BASE'] ?? 'test',
            'port' => $_SERVER['DB_PORT'] ?? 3306,
            'charset' => 'utf8mb4',
        ];
        $dbc = new DbConfig($config['tables']);
        $this->dbm = new DbManager($dbc, $config['connections'], $config['default_connection']);
    }

    protected function tearDown(): void
    {
        $this->dbm->getConnection()->close();
    }

    protected function getTable(string $table = 'test'): string
    {
        return sprintf('`test`.`test_%s`', $table);
    }

    protected function db($table = 'test'): DbQuery
    {
        return $this->dbm->db($table);
    }

    protected function getConfig(string $name = 'default'): array
    {
        $yaml = file_get_contents(__DIR__.'/Fixtures/config/'.$name.'.yml');

        return Yaml::parse($yaml)['alhames_db'];
    }
}
