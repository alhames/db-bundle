<?php

namespace DbBundle\Db;

/**
 * Interface DbQueryFormatterInterface.
 */
interface DbQueryFormatterInterface
{
    /**
     * @param string      $query
     * @param string|null $cacheKey
     * @param int|null    $cacheTime
     *
     * @return string
     */
    public function format(string $query, string $cacheKey = null, int $cacheTime = null): string;
}
