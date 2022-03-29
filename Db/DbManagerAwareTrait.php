<?php

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
     * @param string|DbQuery $alias
     *
     * @throws DbException
     */
    protected function db($alias): DbQuery
    {
        return $this->dbm->db($alias);
    }
}
