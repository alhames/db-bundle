<?php
declare(strict_types=1);

namespace DbBundle\Tests;

use DbBundle\Db\Db;
use DbBundle\Db\DbTable;

/**
 * Class DbTableTest.
 */
class DbTableTest extends AbstractTestCase
{
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
        $this->assertDbQuery($db, ['SELECT *', 'FROM '.$this->getTable().' AS self']);

        $this->assertSame($db, $db->orderBy(null));
        $this->assertSame($db, $db->groupBy(null));
        $this->assertSame($db, $db->limit(1));
        $this->assertSame($db, $db->having(null));
        $this->assertSame($db, $db->setPage(1, 1));
        $this->assertSame($db, $db->index('field'));
    }

    public function testTruncate()
    {
        $db = $this->db()->truncate()->disableSecurity();
        $this->assertDbQuery($db, ['TRUNCATE TABLE '.$this->getTable()]);
    }

    /**
     * @expectedException \DbBundle\Exception\DbException
     */
    public function testTruncateException()
    {
        $this->db()->truncate()->getQuery();
    }

    public function testOptimize()
    {
        $db = $this->db()->optimize();
        $this->assertDbQuery($db, ['OPTIMIZE TABLE '.$this->getTable()]);
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
        $db = $this->db()->select($fields, $options);
        $this->assertDbQuery($db, ['SELECT '.$expected, 'FROM '.$this->getTable().' AS self']);
    }

    public function testJoin()
    {
        $db = $this->db()->select();
        $expected = ['SELECT *', 'FROM '.$this->getTable().' AS self'];
        $this->assertDbQuery($db, $expected);

        $db->join('table1', 't1', 'self.id = t1.test_id');
        $expected[] = 'INNER JOIN '.$this->getTable('table1').' AS `t1` ON (self.id = t1.test_id)';
        $this->assertDbQuery($db, $expected);

        $db->join('table2', 't2', ['self.id' => Db::field('t2.test_id'), 't2.type' => 3], 'LEFT');
        $expected[] = 'LEFT JOIN '.$this->getTable('table2').' AS `t2` ON (self.id = t2.test_id AND t2.type = 3)';
        $this->assertDbQuery($db, $expected);

        $db->join('test', 'self2', ['self.id' => Db::field('self2.id', '<')], 'RIGHT');
        $expected[] = 'RIGHT JOIN '.$this->getTable().' AS `self2` ON (self.id < self2.id)';
        $this->assertDbQuery($db, $expected);
    }

    public function testIndex()
    {
        $db = $this->db()->select()->index(['i1', 'i2']);
        $this->assertDbQuery($db, [
            'SELECT *',
            'FROM '.$this->getTable().' AS self',
            'USE INDEX (`i1`, `i2`)'
        ]);
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
        $db = $this->db()
            ->select()
            ->where($params, $statement);
        $this->assertDbQuery($db, [
            'SELECT *',
            'FROM '.$this->getTable().' AS self',
            'WHERE '.$expected,
        ]);
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
        $db = $this->db()
            ->select()
            ->orderBy($options);
        $this->assertDbQuery($db, [
            'SELECT *',
            'FROM '.$this->getTable().' AS self',
            'ORDER BY '.$expected,
        ]);
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
        $db = $this->db()
            ->select()
            ->groupBy($options);
        $this->assertDbQuery($db, [
            'SELECT *',
            'FROM '.$this->getTable().' AS self',
            'GROUP BY '.$expected,
        ]);
    }

    public function testHaving()
    {
        $db = $this->db()->select()->groupBy(['field']);

        $db->having(['COUNT(*)' => Db::value('>', 2)]);
        $this->assertDbQuery($db, [
            'SELECT *',
            'FROM '.$this->getTable().' AS self',
            'GROUP BY `field`',
            'HAVING COUNT(*) > 2'
        ]);
    }

    public function testLimit()
    {
        $db = $this->db()->select();
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
        $this->db()->select()->limit(null);
    }

    public function testSetPage()
    {
        $db = $this->db()->select()->setPage(3, 10);
        $this->assertDbQuery($db, [
            'SELECT *',
            'FROM '.$this->getTable().' AS self',
            'LIMIT 20, 10',
        ]);
    }

    public function testInsert()
    {
        $db = $this->db()->insert([
            'int' => 1,
            'float' => 1.2,
            'str' => 'string',
            'bool' => false,
            'null' => null,
        ]);
        $this->assertDbQuery($db, [
            'INSERT INTO '.$this->getTable(),
            'SET `int` = 1, `float` = 1.2, `str` = "string", `bool` = 0, `null` = NULL',
        ]);

        $db = $this->db()->insert(['field' => 1], Db::IGNORE);
        $this->assertDbQuery($db, [
            'INSERT IGNORE INTO '.$this->getTable(),
            'SET `field` = 1',
        ]);

        $items = [
            ['name' => 'a', 'size' => 1],
            ['name' => 'b', 'size' => 2],
            ['name' => 'c', 'size' => 3],
        ];
        $db = $this->db()->insert($items);
        $this->assertDbQuery($db, [
            'INSERT INTO '.$this->getTable(),
            '(`name`,`size`) VALUES',
            '("a",1),',
            '("b",2),',
            '("c",3)'
        ]);
    }

    public function testUpdate()
    {
        $db = $this->db()
            ->update([
                'int' => 1,
                'float' => 1.2,
                'str' => 'string',
                'bool' => false,
                'null' => null,
                'field' => Db::field('id'),
            ])
            ->where(['id' => 123]);
        $this->assertDbQuery($db, [
            'UPDATE '.$this->getTable(),
            'SET `int` = 1, `float` = 1.2, `str` = "string", `bool` = 0, `null` = NULL, `field` = `id`',
            'WHERE `id` = 123'
        ]);

        $db = $this->db()
            ->update(['field' => 1], Db::IGNORE)
            ->where(['id' => 123]);
        $this->assertDbQuery($db, [
            'UPDATE IGNORE '.$this->getTable(),
            'SET `field` = 1',
            'WHERE `id` = 123'
        ]);
    }

    /**
     * @expectedException \DbBundle\Exception\DbException
     */
    public function testUpdateException()
    {
        $this->db()->update(['field' => 1])->getQuery();
    }

    public function testDelete()
    {
        $db = $this->db()
            ->delete()
            ->where(['id' => 123]);
        $this->assertDbQuery($db, [
            'DELETE FROM '.$this->getTable(),
            'WHERE `id` = 123'
        ]);

        $db = $this->db()
            ->delete(Db::IGNORE)
            ->where(['id' => 123]);
        $this->assertDbQuery($db, [
            'DELETE IGNORE FROM '.$this->getTable(),
            'WHERE `id` = 123'
        ]);
    }

    /**
     * @expectedException \DbBundle\Exception\DbException
     */
    public function testDeleteException()
    {
        $this->db()->delete()->getQuery();
    }

    public function testReplace()
    {
        $db = $this->db()->replace([
            'int' => 1,
            'float' => 1.2,
            'str' => 'string',
            'bool' => false,
            'null' => null,
        ]);
        $this->assertDbQuery($db, [
            'REPLACE INTO '.$this->getTable(),
            'SET `int` = 1, `float` = 1.2, `str` = "string", `bool` = 0, `null` = NULL',
        ]);
    }

    public function testSubQuery()
    {
        $subQuery = $this->db()->select()->where(['id' => 1]);
        $db = $this->db($subQuery)->select()->limit(1);

        $this->assertDbQuery($db, [
            'SELECT *',
            'FROM (',
            'SELECT *',
            'FROM '.$this->getTable().' AS self',
            'WHERE `id` = 1',
            ') AS self',
            'LIMIT 1',
        ]);
    }

    public function testOnDuplicateKey()
    {
        $db = $this->db()->insert(['field' => 1])->onDuplicateKey(['field' => 2]);
        $this->assertDbQuery($db, [
            'INSERT INTO '.$this->getTable(),
            'SET `field` = 1',
            'ON DUPLICATE KEY UPDATE `field` = 2',
        ]);

        $db = $this->db()->insert(['field' => 1])->onDuplicateKey([
            'field2' => Db::field('field3'),
            'field3' => Db::field('`field3` + `field2`')
        ]);
        $this->assertDbQuery($db, [
            'INSERT INTO '.$this->getTable(),
            'SET `field` = 1',
            'ON DUPLICATE KEY UPDATE `field2` = `field3`, `field3` = `field3` + `field2`',
        ]);

        $items = [
            ['name' => 'a', 'size' => 1],
            ['name' => 'b', 'size' => 2],
            ['name' => 'c', 'size' => 3],
        ];
        $db = $this->db()->insert($items)->onDuplicateKey(['field' => Db::field('VALUE(`name`)')]);
        $this->assertDbQuery($db, [
            'INSERT INTO '.$this->getTable(),
            '(`name`,`size`) VALUES',
            '("a",1),',
            '("b",2),',
            '("c",3)',
            'ON DUPLICATE KEY UPDATE `field` = VALUE(`name`)',
        ]);
    }

    public function testSetCaching()
    {
        $db = $this->db()->select()->setCaching('key', 60, true);
        $this->assertAttributeSame('key', 'cacheKey', $db);
        $this->assertAttributeSame(60, 'cacheTime', $db);
        $this->assertAttributeSame(true, 'cacheRebuild', $db);
    }

    public function testGetRow()
    {
        $result = [
            ['id' => 1, 'name' => 'Ippolit'],
            ['id' => 2, 'name' => 'Matwey'],
            ['id' => 3, 'name' => 'Ibragim'],
            ['id' => 4, 'name' => 'Matwey'],
        ];
        $db = $this->db()->select();
        (\Closure::bind(function($db, $result) { $db->result = $result; }, null, $db))($db, $result);

        // Single
        $this->assertSame(['id' => 1, 'name' => 'Ippolit'], $db->getRow());
        $this->assertSame('Ippolit', $db->getRow('name'));

        // Multiple
        $this->assertSame($result, $db->getRows());
        $this->assertSame([
            1 => ['id' => 1, 'name' => 'Ippolit'],
            2 => ['id' => 2, 'name' => 'Matwey'],
            3 => ['id' => 3, 'name' => 'Ibragim'],
            4 => ['id' => 4, 'name' => 'Matwey'],
        ], $db->getRows('id'));
        $this->assertSame([
            'Ippolit' => ['id' => 1, 'name' => 'Ippolit'],
            'Matwey' => ['id' => 4, 'name' => 'Matwey'],
            'Ibragim' => ['id' => 3, 'name' => 'Ibragim'],
        ], $db->getRows('name'));
        $this->assertSame([
            1 => 'Ippolit',
            2 => 'Matwey',
            3 => 'Ibragim',
            4 => 'Matwey',
        ], $db->getRows('id', 'name'));
        $this->assertSame([
            'Ippolit',
            'Matwey',
            'Ibragim',
            'Matwey',
        ], $db->getRows(null, 'name'));
        $this->assertSame([
            'Ippolit' => [1],
            'Matwey' => [2, 4],
            'Ibragim' => [3],
        ], $db->getRows('name', 'id', true));
    }

    /**
     * @param DbTable $db
     * @param array   $expected
     */
    protected function assertDbQuery(DbTable $db, array $expected)
    {
        $this->assertSame(implode(PHP_EOL, $expected), $db->getQuery());
    }
}
