<?php

declare(strict_types=1);

namespace Alhames\DbBundle\Db;

interface DbQueryFormatterInterface
{
    public function format(string $query, ?string $cacheKey = null, ?int $cacheTime = null): string;
}
