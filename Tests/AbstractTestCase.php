<?php

namespace DbBundle\Tests;

use DbBundle\Db\DbConfig;
use DbBundle\Db\DbManager;
use PHPUnit\Framework\TestCase;

/**
 * Class AbstractTestCase.
 */
class AbstractTestCase extends TestCase
{
    const PREFIX = 'test_';

    /** @var string */
    protected $database = 'tests';

    /** @var DbManager */
    protected $dbm;

    protected function setUp()
    {
        $this->database = $GLOBALS['db_database'] ?? $this->database;

        $dbc = new DbConfig([
            'test' => ['table' => self::PREFIX.'test', 'database' => $this->database],
            'table1' => ['table' => self::PREFIX.'table1', 'database' => $this->database],
            'table2' => ['table' => self::PREFIX.'table2', 'database' => $this->database],
        ]);
        $this->dbm = new DbManager($dbc, [
            'default' => [
                'host' => $GLOBALS['db_host'],
                'username' => $GLOBALS['db_username'],
                'password' => $GLOBALS['db_password'],
                'database' => $this->database,
                'port' => $GLOBALS['db_port'] ?? 3306,
                'charset' => 'utf8mb4',
            ],
        ]);
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
        return sprintf('`%s`.`%s%s`', $this->database, self::PREFIX, $table);
    }

    /**
     * @param string $table
     *
     * @return \DbBundle\Db\DbTable
     */
    protected function db($table = 'test')
    {
        return $this->dbm->db($table);
    }
}
