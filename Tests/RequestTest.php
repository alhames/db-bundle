<?php
declare(strict_types=1);

namespace Alhames\DbBundle\Tests;

use Alhames\DbBundle\Db\Db;

/**
 * Class RequestTest.
 */
class RequestTest extends AbstractTestCase
{
    protected function setUp()
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

    protected function tearDown()
    {
        $this->dbm->getConnection()->query('DROP TABLE '.$this->getTable());
        parent::tearDown();
    }

    public function testInsert()
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
    public function testSelect()
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
    public function testUpdate()
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
    public function testDelete()
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
    public function testTruncate()
    {
        $this->db()->truncate()->disableSecurity()->exec();
        $this->assertSame(0, $this->getRowCount());
    }

    /**
     * @param string $where
     *
     * @return int
     */
    protected function getRowCount(string $where = '')
    {
        $result = $this->dbm->getConnection()
            ->query('SELECT COUNT(*) AS c FROM '.$this->getTable().($where ? ' WHERE '.$where : ''));

        return (int) $result[0]['c'];
    }
}
