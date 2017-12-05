<?php

namespace Alhames\DbBundle\Db;

use Alhames\DbBundle\Exception\DbException;

/**
 * Class DbConfig.
 */
class DbConfig
{
    /** @var array */
    private $tables;

    /**
     * DbConfig constructor.
     *
     * @param array $tables
     */
    public function __construct(array $tables = [])
    {
        $this->tables = $tables;
    }

    /**
     * @param string $alias
     *
     * @throws DbException
     *
     * @return array
     */
    public function get(string $alias): array
    {
        if (!isset($this->tables[$alias])) {
            throw new DbException(null, sprintf('Unknown table "%s".', $alias));
        }

        return $this->tables[$alias];
    }

    /**
     * @param string $alias
     *
     * @throws DbException
     *
     * @return string
     */
    public function getTable(string $alias): string
    {
        if (!isset($this->tables[$alias])) {
            throw new DbException(null, sprintf('Unknown table "%s".', $alias));
        }

        $table = '`'.$this->tables[$alias]['table'].'`';
        if (isset($this->tables[$alias]['database'])) {
            $table = '`'.$this->tables[$alias]['database'].'`.'.$table;
        }

        return $table;
    }
}
