<?php
declare(strict_types=1);

namespace Alhames\DbBundle\Tests;

use Alhames\DbBundle\Db\Db;

class RequestTest extends AbstractTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $connection = $this->dbm->getConnection();
        $connection->query('
            CREATE TABLE IF NOT EXISTS '.$this->getTable().' (
                `id` SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `name` TEXT NULL DEFAULT NULL,
                `time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB
        ');
        $connection->query('SET time_zone = "+00:00"');
        $connection->query('INSERT INTO '.$this->getTable().' (`name`) VALUES ("a"), ("b"), ("c"), ("d")');
    }

    protected function tearDown(): void
    {
        $this->dbm->getConnection()->query('DROP TABLE '.$this->getTable());
        parent::tearDown();
    }

    public function testInsert(): void
    {
        $insertId = $this->db()->insert(['name' => 'aaa'])->getInsertId();
        $this->assertSame(5, $insertId);
        $this->assertSame(5, $this->getRowCount());

        $this->db()->insert([
            ['name' => 'bbb'],
            ['name' => 'ccc'],
            ['name' => 'ddd'],
        ])->exec();
        $this->assertSame(8, $this->getRowCount());
    }

    /**
     * @depends testInsert
     */
    public function testSelect(): void
    {
        $time = new \DateTimeImmutable('+1hour', new \DateTimeZone('UTC'));
        $insertId = $this->db()->insert(['name' => 'aaa', 'time' => $time])->getInsertId();
        $this->assertSame(5, $insertId);

        $result = $this->db()
            ->select()
            ->where(['time' => $time])
            ->getRow();
        $this->assertSame(['id' => '5', 'name' => 'aaa', 'time' => $time->format('Y-m-d H:i:s')], $result);

        $result = $this->db()
            ->select('id')
            ->where(['time' => Db::value('<', $time)])
            ->orderBy(['name' => 'DESC'])
            ->getRows(null, 'id');
        $this->assertSame(['4', '3', '2', '1'], $result);

        $name = $this->db()
            ->select('name')
            ->where(['id' => 3])
            ->limit(1)
            ->getRow('name');
        $this->assertSame('c', $name);
    }

    /**
     * @depends testSelect
     */
    public function testRowCount()
    {
        $expectedRowCount = $this->getRowCount();

        $db1 = $this->db()->select(null, Db::CALC_FOUND_ROWS);
        $result1 = $db1->getRows();
        $count1 = $db1->getRowCount();
        $this->assertCount($expectedRowCount, $result1);
        $this->assertSame($expectedRowCount, $count1);

        // check same things in different order
        $db2 = $this->db()->select(null, Db::CALC_FOUND_ROWS);
        $count2 = $db2->getRowCount();
        $result2 = $db2->getRows();
        $this->assertCount($expectedRowCount, $result2);
        $this->assertSame($expectedRowCount, $count2);
    }

    /**
     * @depends testSelect
     */
    public function testUpdate(): void
    {
        $this->db()
            ->update(['name' => 'z'])
            ->where(['id' => 3])
            ->exec();
        $name = $this->db()
            ->select('name')
            ->where(['id' => 3])
            ->limit(1)
            ->getRow('name');
        $this->assertSame('z', $name);
    }

    /**
     * @depends testUpdate
     */
    public function testDelete(): void
    {
        $this->db()
            ->delete()
            ->where(['id' => 3])
            ->exec();
        $this->assertSame(3, $this->getRowCount());

        $name = $this->db()
            ->select('name')
            ->where(['id' => 3])
            ->limit(1)
            ->getRow('name');
        $this->assertFalse($name);
    }

    /**
     * @depends testDelete
     */
    public function testTruncate(): void
    {
        $this->db()->truncate()->disableSecurity()->exec();
        $this->assertSame(0, $this->getRowCount());
    }

    protected function getRowCount(string $where = ''): int
    {
        $result = $this->dbm->getConnection()
            ->query('SELECT COUNT(*) AS c FROM '.$this->getTable().($where ? ' WHERE '.$where : ''));

        return (int) $result[0]['c'];
    }
}
