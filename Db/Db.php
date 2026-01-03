<?php

declare(strict_types=1);

namespace Alhames\DbBundle\Db;

class Db
{
    public const string CALC_FOUND_ROWS = 'SQL_CALC_FOUND_ROWS';
    public const string DISTINCT = 'DISTINCT';
    public const string IGNORE = 'IGNORE';

    public const string INNER = 'INNER';
    public const string LEFT = 'LEFT';
    public const string RIGHT = 'RIGHT';

    public static function value(string $operator, mixed $value): DbValue
    {
        return new DbValue($value, $operator, false);
    }

    public static function between(mixed $from, mixed $to): DbValue
    {
        return new DbValue([$from, $to], 'BETWEEN', false);
    }

    public static function less(mixed $value): DbValue
    {
        return new DbValue($value, '<', false);
    }

    public static function more(mixed $value): DbValue
    {
        return new DbValue($value, '>', false);
    }

    public static function not(mixed $value): DbValue
    {
        return new DbValue($value, '!=', false);
    }

    public static function like(mixed $value): DbValue
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
