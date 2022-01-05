<?php

namespace Alhames\DbBundle\Db;

interface DbQueryFormatterInterface
{
    public function format(string $query, string $cacheKey = null, int $cacheTime = null): string;
}
