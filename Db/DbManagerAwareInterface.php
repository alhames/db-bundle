<?php

namespace Alhames\DbBundle\Db;

/**
 * Interface DbManagerAwareInterface.
 */
interface DbManagerAwareInterface
{
    /**
     * @param DbManager $dbm
     *
     * @return static
     */
    public function setDbManager(DbManager $dbm);
}
