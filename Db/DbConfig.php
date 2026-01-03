<?php

declare(strict_types=1);

namespace Alhames\DbBundle\Db;

use Alhames\DbBundle\Exception\DbException;

class DbConfig
{
    private array $tables;

    public function __construct(array $tables = [])
    {
        $this->tables = $tables;
    }

    public function get(string $alias): array
    {
        if (!isset($this->tables[$alias])) {
            throw new DbException(null, sprintf('Unknown table "%s".', $alias));
        }

        return $this->tables[$alias];
    }

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
