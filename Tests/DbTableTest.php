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

    public function testSimple()
    {
        $db = $this->dbm->db('test');
        $this->assertInstanceOf(DbTable::class, $db);
        $this->assertSame('test', $db->getAlias());
        $this->assertSame($this->getTable(), $db->getTable());

        $this->assertSame('', $db->getMethod());
        $this->assertSame($db, $db->select());
        $this->assertSame('select', $db->getMethod());

        $this->assertSame($db, $db->where());
        $this->assertSame('SELECT *'.PHP_EOL.'FROM '.$this->getTable().' AS self', $db->getQuery());

        $this->assertSame($db, $db->orderBy(null));
        $this->assertSame($db, $db->groupBy(null));
        $this->assertSame($db, $db->limit(1));
        $this->assertSame($db, $db->having(null));
        $this->assertSame($db, $db->setPage(1, 1));
        $this->assertSame($db, $db->index('field'));
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
        $this->assertSame('SELECT '.$expected.PHP_EOL.'FROM '.$this->getTable().' AS self', $db->getQuery());
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
        $this->assertSame('SELECT *'.PHP_EOL.'FROM '.$this->getTable().' AS self'.PHP_EOL.'WHERE '.$expected, $db->getQuery());
    }

    public function testLimit()
    {
        $db = $this->dbm->db('test')->select();
        $expectedPrefix = 'SELECT *'.PHP_EOL.'FROM '.$this->getTable().' AS self'.PHP_EOL.'LIMIT ';

        $db->limit(1);
        $this->assertSame($expectedPrefix.'1', $db->getQuery());

        $db->limit(1, 2);
        $this->assertSame($expectedPrefix.'1, 2', $db->getQuery());

        $db->limit(0, 1000);
        $this->assertSame($expectedPrefix.'0, 1000', $db->getQuery());

        $db->limit(100, null);
        $this->assertSame($expectedPrefix.'100', $db->getQuery());
    }

    /**
     * @expectedException \TypeError
     */
    public function testLimitExceptions()
    {
        $this->dbm->db('test')->select()->limit(null);
    }

    /**
     * @return array
     */
    public function provideOrderBy()
    {
        return [
            ['`field` DESC', ['field' => 'DESC']],
            ['field', 'field'],
            ['`field`', ['field']],
            ['`field1`, `field2`', ['field1', 'field2']],
            ['`field1` ASC, `field2` DESC', ['field1' => 'ASC', 'field2' => 'DESC']],
            ['COUNT(*) DESC', ['COUNT(*)' => 'DESC']],
            ['self.field ASC', ['self.field' => 'ASC']],
            ['field ASC', 'field ASC'],
            ['table1.field, table2.field DESC', ['table1.field', 'table2.field' => 'DESC']],
        ];
    }

    /**
     * @dataProvider provideOrderBy
     *
     * @param $expected
     * @param $options
     */
    public function testOrderBy($expected, $options)
    {
        $db = $this->dbm->db('test')
            ->select()
            ->orderBy($options);
        $this->assertSame('SELECT *'.PHP_EOL.'FROM '.$this->getTable().' AS self'.PHP_EOL.'ORDER BY '.$expected, $db->getQuery());
    }

    public function provideGroupBy()
    {
        return [
            ['field', 'field'],
            ['`field`', ['field']],
            ['`field1`, `field2`', ['field1', 'field2']],
        ];
    }

    /**
     * @dataProvider provideGroupBy
     *
     * @param $expected
     * @param $options
     */
    public function testGroupBy($expected, $options)
    {
        $db = $this->dbm->db('test')
            ->select()
            ->groupBy($options);
        $this->assertSame('SELECT *'.PHP_EOL.'FROM '.$this->getTable().' AS self'.PHP_EOL.'GROUP BY '.$expected, $db->getQuery());
    }

    /**
     * @return string
     */
    private function getTable()
    {
        return sprintf('`%s`.`%s`', $this->database, self::TABLE);
    }
}
