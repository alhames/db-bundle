<?php

namespace Alhames\DbBundle\Tests;

use Alhames\DbBundle\Db\DbConfig;
use Alhames\DbBundle\Db\DbManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * Class AbstractTestCase.
 */
abstract class AbstractTestCase extends TestCase
{
    /** @var DbManager */
    protected $dbm;

    protected function setUp()
    {
        $config = $this->getConfig('test');
        $config['connections']['test'] = [
            'host' => $GLOBALS['db_host'],
            'username' => $GLOBALS['db_username'],
            'password' => $GLOBALS['db_password'],
            'database' => 'test',
            'port' => $GLOBALS['db_port'] ?? 3306,
            'charset' => 'utf8mb4',
        ];

        $dbc = new DbConfig($config['tables']);
        $this->dbm = new DbManager($dbc, $config['connections'], $config['default_connection']);
    }

    protected function tearDown()
    {
        $this->dbm->getConnection()->close();
    }

    /**
     * @param string $table
     *
     * @return string
     */
    protected function getTable(string $table = 'test')
    {
        return sprintf('`test`.`test_%s`', $table);
    }

    /**
     * @param string $table
     *
     * @return \Alhames\DbBundle\Db\DbQuery
     */
    protected function db($table = 'test')
    {
        return $this->dbm->db($table);
    }

    /**
     * @param string $name
     *
     * @return array
     */
    protected function getConfig(string $name = 'default'): array
    {
        $yaml = file_get_contents(__DIR__.'/Fixtures/config/'.$name.'.yml');

        return Yaml::parse($yaml)['alhames_db'];
    }
}
