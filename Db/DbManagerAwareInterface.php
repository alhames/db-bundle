<?php

declare(strict_types=1);

namespace Alhames\DbBundle\Db;

interface DbManagerAwareInterface
{
    public function setDbManager(DbManager $dbm): void;
}
