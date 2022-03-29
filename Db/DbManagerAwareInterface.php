<?php

namespace Alhames\DbBundle\Db;

interface DbManagerAwareInterface
{
    public function setDbManager(DbManager $dbm): void;
}
