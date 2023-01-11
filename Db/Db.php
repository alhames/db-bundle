<?php

namespace Alhames\DbBundle\Db;

class Db
{
    public const CALC_FOUND_ROWS = 'SQL_CALC_FOUND_ROWS';
    public const DISTINCT = 'DISTINCT';
    public const IGNORE = 'IGNORE';

    public const INNER = 'INNER';
    public const LEFT = 'LEFT';
    public const RIGHT = 'RIGHT';

    /**
     * @param mixed $value
     */
    public static function value(string $operator, $value): DbValue
    {
        return new DbValue($value, $operator, false);
    }

    /**
     * @param mixed $from
     * @param mixed $to
     */
    public static function between($from, $to): DbValue
    {
        return new DbValue([$from, $to], 'BETWEEN', false);
    }

    /**
     * @param mixed $value
     */
    public static function less($value): DbValue
    {
        return new DbValue($value, '<', false);
    }

    /**
     * @param mixed $value
     */
    public static function more($value): DbValue
    {
        return new DbValue($value, '>', false);
    }

    /**
     * @param mixed $value
     */
    public static function not($value): DbValue
    {
        return new DbValue($value, '!=', false);
    }

    /**
     * @param mixed $value
     */
    public static function like($value): DbValue
    {
        return new DbValue($value, 'LIKE', false);
    }

    public static function field(string $name, string $operator = '='): DbValue
    {
        return new DbValue($name, $operator, true);
    }

    public static function escapeLike(string $string): string
    {
        return addcslashes($string, '_%\\');
    }
}
