<?php

declare(strict_types=1);

namespace Alhames\DbBundle\Db;

use Alhames\DbBundle\Exception\DbException;

trait DbManagerAwareTrait
{
    protected ?DbManager $dbm = null;

    public function setDbManager(DbManager $dbm): void
    {
        $this->dbm = $dbm;
    }

    /**
     * @throws DbException
     */
    protected function db(string|DbQuery $alias): DbQuery
    {
        return $this->dbm->db($alias);
    }
}
