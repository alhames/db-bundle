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
     * @param string|DbQuery $alias
     *
     * @throws DbException
     *
     * @return DbQuery
     */
    protected function db($alias): DbQuery
    {
        return $this->dbm->db($alias);
    }
}
