<?php

namespace DbBundle\Db;

/**
 * Class Db.
 */
class Db
{
    const CALC_FOUND_ROWS = 'SQL_CALC_FOUND_ROWS';
    const DISTINCT = 'DISTINCT';
    const IGNORE = 'IGNORE';

    /**
     * @param string $operator
     * @param mixed  $value
     *
     * @return DbValue
     */
    public static function value(string $operator, $value): DbValue
    {
        return new DbValue($value, $operator, false);
    }

    /**
     * @param string $name
     * @param string $operator
     *
     * @return DbValue
     */
    public static function field(string $name, string $operator = '='): DbValue
    {
        return new DbValue($name, $operator, true);
    }

    /**
     * @param string $string
     *
     * @return string
     */
    public static function escapeLike(string $string): string
    {
        return addcslashes($string, '_%\\');
    }
}
