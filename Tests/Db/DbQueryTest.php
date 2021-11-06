<?php
declare(strict_types=1);

namespace Alhames\DbBundle\Tests\Db;

use Alhames\DbBundle\Db\Db;
use Alhames\DbBundle\Db\DbQuery;
use Alhames\DbBundle\Exception\DbException;
use Alhames\DbBundle\Tests\AbstractTestCase;

class DbQueryTest extends AbstractTestCase
{
    public function testSimple(): void
    {
        $db = $this->dbm->db('test');
        $this->assertInstanceOf(DbQuery::class, $db);
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

    public function testTruncate(): void
    {
        $db = $this->db()->truncate()->disableSecurity();
        $this->assertDbQuery($db, ['TRUNCATE TABLE '.$this->getTable()]);
    }

    public function testTruncateException(): void
    {
        $this->expectException(DbException::class);
        $this->db()->truncate()->getQuery();
    }

    public function testOptimize(): void
    {
        $db = $this->db()->optimize();
        $this->assertDbQuery($db, ['OPTIMIZE TABLE '.$this->getTable()]);
    }

    public function provideSelect(): array
    {
        return [
            ['*'],
            ['*', '*'],
            ['DISTINCT *', null, Db::DISTINCT],
            ['SQL_CALC_FOUND_ROWS DISTINCT *', null, 'SQL_CALC_FOUND_ROWS DISTINCT'],
            ['self.*', 'self.*'],
            ['`test_field`', 'test_field'],
            ['self.test_field', 'self.test_field'],
            ['field_one, field_two', 'field_one, field_two'],
            ['self.field_one, self.field_two', 'self.field_one, self.field_two'],
            ['`field_one`, `field_two`', ['field_one', 'field_two']],
            ['self.field_one, self.field_two', ['self.field_one', 'self.field_two']],
            ['`field_one`, `field_two`', '`field_one`, `field_two`'],
            ['`field_one` AS `one`, `field_two`', ['field_one' => 'one', 'field_two']],
            ['COUNT(*)', 'COUNT(*)'],
            ['COUNT(*) AS `c`', ['COUNT(*)' => 'c']],
            ['COUNT(*) AS c', 'COUNT(*) AS c'],
            ['*, COUNT(*)', '*, COUNT(*)'],
            ['*, COUNT(*)', ['*', 'COUNT(*)']],
            ['*, COUNT(*) AS `c`', ['*', 'COUNT(*)' => 'c']],
        ];
    }

    /**
     * @dataProvider provideSelect
     *
     * @param string            $expected
     * @param string|array|null $fields
     * @param string|null       $options
     */
    public function testSelect(string $expected, $fields = null, ?string $options = null): void
    {
        $db = $this->db()->select($fields, $options);
        $this->assertDbQuery($db, ['SELECT '.$expected, 'FROM '.$this->getTable().' AS self']);
    }

    public function testJoin(): void
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

    public function testIndex(): void
    {
        $db = $this->db()->select()->index(['i1', 'i2']);
        $this->assertDbQuery($db, [
            'SELECT *',
            'FROM '.$this->getTable().' AS self',
            'USE INDEX (`i1`, `i2`)'
        ]);

        $db = $this->db()->select()->index('main', 'FORCE', 'GROUP BY');
        $this->assertDbQuery($db, [
            'SELECT *',
            'FROM '.$this->getTable().' AS self',
            'FORCE INDEX FOR GROUP BY (`main`)'
        ]);
    }

    public function textIndexWithInvalidAction(): void
    {
        $this->expectException(DbException::class);
        $this->db()->select()->index('main', 'CAT');
    }

    public function textIndexWithInvalidPurpose(): void
    {
        $this->expectException(DbException::class);
        $this->db()->select()->index('main', 'USE', 'CAT');
    }

    public function provideWhere(): array
    {
        return [
            // Test Types
            [
                '`field` = 1',
                ['field' => 1],
            ],
            [
                '`field` = "a"',
                ['field' => 'a'],
            ],
            [
                '`field` = 1.1',
                ['field' => 1.1],
            ],
            [
                '`field` IS NULL',
                ['field' => null],
            ],
            [
                '`field` = "2017-07-12 18:55:43"',
                ['field' => new \DateTime('2017-07-12 18:55:43')],
            ],
            [
                '`field` = 0',
                ['field' => false],
            ],
            [
                '`field` = 1',
                ['field' => true],
            ],

            // Test IN()
            [
                '`field` IN (1)',
                ['field' => [1]],
            ],
            [
                '`field` IN (1,2)',
                ['field' => [1, 2]],
            ],
            [
                '`field` IN ("a",2,NULL)',
                ['field' => ['a', 2, null]],
            ],

            // Test AND
            [
                '`field_one` = 1 AND `field_two` = 2',
                ['field_one' => 1, 'field_two' => 2],
            ],
            [
                '`field_one` = 1 AND `field_two` = 2 AND `field_three` = 3',
                ['field_one' => 1, 'field_two' => 2, 'field_three' => 3],
            ],

            // Test Db::value()
            [
                '`field` = 1',
                ['field' => Db::value('=', 1)],
            ],
            [
                '`field` > 1',
                ['field' => Db::value('>', 1)],
            ],
            [
                '`field` <= 1',
                ['field' => Db::value('<=', 1)],
            ],
            [
                '`field` IN (1,2)',
                ['field' => Db::value('=', [1, 2])],
            ],
            [
                '`field` IN (1,2)',
                ['field' => Db::value('IN', [1, 2])],
            ],
            [
                '`field` != 1',
                ['field' => Db::value('!=', 1)],
            ],
            [
                '`field` NOT IN (1,2)',
                ['field' => Db::value('!=', [1, 2])],
            ],
            [
                '`field` NOT IN (1,2)',
                ['field' => Db::value('NOT IN', [1, 2])],
            ],
            [
                '`field` BETWEEN 1 AND 2',
                ['field' => Db::value('BETWEEN', [1, 2])],
            ],
            [
                '`field` BETWEEN "2017-07-11 18:55:43" AND "2017-07-12 18:55:43"',
                ['field' => Db::value('BETWEEN', [new \DateTime('2017-07-11 18:55:43'), new \DateTime('2017-07-12 18:55:43')])],
            ],

            // Test LIKE
            [
                '`field` LIKE "abc"',
                ['field' => Db::value('LIKE', 'abc')],
            ],
            [
                '`field` LIKE "%a_c%"',
                ['field' => Db::value('LIKE', '%a_c%')],
            ],
            [
                '`field` LIKE "%a\\\\_c\\\\%"',
                ['field' => Db::value('LIKE', '%'.Db::escapeLike('a_c%'))],
            ],

            // Test escaping
            [
                '`field` = "a\" AND `b` = \"0"',
                ['field' => 'a" AND `b` = "0'],
            ],

            // Test field
            [
                'self.a = "a"',
                ['self.a' => 'a'],
            ],
            [
                'self.a = `b`',
                ['self.a' => Db::field('b')],
            ],
            [
                'self.a = self.b',
                ['self.a' => Db::field('self.b')],
            ],
        ];
    }

    /**
     * @dataProvider provideWhere
     *
     * @param string      $expected
     * @param array|null  $params
     * @param string|null $statement
     */
    public function testWhere(string $expected, ?array $params = null, ?string $statement = null): void
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

    public function provideOrderBy(): array
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
     * @param string            $expected
     * @param string|array|null $options
     */
    public function testOrderBy(string $expected, $options): void
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

    public function provideGroupBy(): array
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
     * @param string            $expected
     * @param string|array|null $options
     */
    public function testGroupBy(string $expected, $options): void
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

    public function testHaving(): void
    {
        $db = $this->db()->select()->groupBy(['field']);

        $db->having(['COUNT(*)' => Db::more(2)]);
        $this->assertDbQuery($db, [
            'SELECT *',
            'FROM '.$this->getTable().' AS self',
            'GROUP BY `field`',
            'HAVING COUNT(*) > 2'
        ]);
    }

    public function testOffset(): void
    {
        $db = $this->db()->select()->offset(2);
        $this->assertSame('SELECT *'.PHP_EOL.'FROM '.$this->getTable().' AS self'.PHP_EOL.'OFFSET 2', $db->getQuery());
    }

    public function testLimit(): void
    {
        $db = $this->db()->select();
        $expectedPrefix = 'SELECT *'.PHP_EOL.'FROM '.$this->getTable().' AS self'.PHP_EOL.'LIMIT ';

        $db->limit(1);
        $this->assertSame($expectedPrefix.'1', $db->getQuery());

        $db->limit(100)->offset(200);
        $this->assertSame($expectedPrefix.'100'.PHP_EOL.'OFFSET 200', $db->getQuery());
    }

    public function testSetPage(): void
    {
        $db = $this->db()->select()->setPage(3, 10);
        $this->assertDbQuery($db, [
            'SELECT *',
            'FROM '.$this->getTable().' AS self',
            'LIMIT 10',
            'OFFSET 20',
        ]);
    }

    public function testInsert(): void
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

    public function testUpdate(): void
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

    public function testUpdateException(): void
    {
        $this->expectException(DbException::class);
        $this->db()->update(['field' => 1])->getQuery();
    }

    public function testDelete(): void
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

    public function testDeleteException(): void
    {
        $this->expectException(DbException::class);
        $this->db()->delete()->getQuery();
    }

    public function testReplace(): void
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

    public function testSubQuery(): void
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

    public function testSubQueryException(): void
    {
        $this->expectException(DbException::class);
        $subQuery = $this->db()->select()->where(['id' => 1]);
        $this->db($subQuery)->insert(['a' => 'b'])->getQuery();
    }

    public function testOnDuplicateKey(): void
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

    public function testSetCaching(): void
    {
        $db = $this->db()->select()->setCaching('key', 60, true);
        $this->assertAttributeSame('key', 'cacheKey', $db);
        $this->assertAttributeSame(60, 'cacheTime', $db);
        $this->assertAttributeSame(true, 'cacheRebuild', $db);
    }

    public function testSetCachingException(): void
    {
        $this->expectException(DbException::class);
        $this->db()->delete()->setCaching('key', 60, true);
    }

    public function testGetRow(): void
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

    protected function assertDbQuery(DbQuery $db, array $expected): void
    {
        $this->assertSame(implode(PHP_EOL, $expected), $db->getQuery());
    }
}
