<?php
declare(strict_types=1);

namespace DbBundle\Tests;

use DbBundle\Db\DbConfig;
use DbBundle\Db\DbManager;
use DbBundle\Db\DbTable;
use PHPUnit\Framework\TestCase;

/**
 * Class DbTableTest.
 */
class DbTableTest extends TestCase
{
    const TABLE = 'test_table';

    /** @var string */
    protected $database = 'tests';

    /** @var DbManager */
    protected $dbm;

    protected function setUp()
    {
        $this->database = $GLOBALS['db_database'] ?? $this->database;

        $dbc = new DbConfig(['test' => ['table' => self::TABLE, 'database' => $this->database]]);
        $this->dbm = new DbManager($dbc, ['default' => [
            'host' => $GLOBALS['db_host'],
            'username' => $GLOBALS['db_username'],
            'password' => $GLOBALS['db_password'],
            'database' => $this->database,
            'port' => $GLOBALS['db_port'] ?? 3306,
            'charset' => 'utf8mb4',
        ]]);
    }

    protected function tearDown()
    {
        $this->dbm->getConnection()->close();
    }

    public function testConstructor()
    {
        $db = $this->dbm->db('test');
        $this->assertInstanceOf(DbTable::class, $db);
        $this->assertSame('test', $db->getAlias());
        $this->assertSame(sprintf('`%s`.`%s`', $this->database, self::TABLE), $db->getTable());

        $this->assertSame('', $db->getMethod());
        $db->select();
        $this->assertSame('select', $db->getMethod());

        $db->where();
        $this->assertSame('SELECT *'.PHP_EOL.'FROM '.$db->getTable().' AS self', $db->getQuery());
    }

    /**
     * @return array
     */
    public function provideSelect()
    {
        return include __DIR__.'/Fixtures/DbTable/select.php';
    }

    /**
     * @dataProvider provideSelect
     *
     * @param $expected
     * @param $fields
     * @param $options
     */
    public function testSelect($expected, $fields = null, $options = null)
    {
        $db = $this->dbm->db('test');
        $db->select($fields, $options);
        $this->assertSame('SELECT '.$expected.PHP_EOL.'FROM '.$db->getTable().' AS self', $db->getQuery());
    }

    /**
     * @return array
     */
    public function provideWhere()
    {
        return include __DIR__.'/Fixtures/DbTable/where.php';
    }

    /**
     * @dataProvider provideWhere
     *
     * @param $expected
     * @param $params
     * @param $statement
     */
    public function testWhere($expected, $params = null, $statement = null)
    {
        $db = $this->dbm->db('test')
            ->select()
            ->where($params, $statement);
        $this->assertSame('SELECT *'.PHP_EOL.'FROM '.$db->getTable().' AS self'.PHP_EOL.'WHERE '.$expected, $db->getQuery());
    }
}
