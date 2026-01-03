<?php

declare(strict_types=1);

namespace Alhames\DbBundle\Db;

class DbValue
{
    public mixed $value;
    public string $operator;
    public bool $isField;

    public function __construct(mixed $value, string $operator = '=', bool $isField = false)
    {
        $this->value = $value;
        $this->operator = $operator;
        $this->isField = $isField;
    }
}
