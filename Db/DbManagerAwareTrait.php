<?php

namespace Alhames\DbBundle\Db;

use Alhames\DbBundle\Exception\DbException;

/**
 * trait DbManagerAwareTrait.
 */
trait DbManagerAwareTrait
{
    /** @var DbManager */
    protected $dbm;

    /**
     * @param DbManager $dbm
     *
     * @return static
     */
    public function setDbManager(DbManager $dbm)
    {
        $this->dbm = $dbm;

        return $this;
    }

    /**
     * @param string|DbTable $alias
     *
     * @throws DbException
     *
     * @return DbTable
     */
    protected function db($alias): DbTable
    {
        return $this->dbm->db($alias);
    }
}
