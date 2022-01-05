<?php

namespace Alhames\DbBundle\Db;

class DbValue
{
    /** @var mixed */
    public $value;
    public string $operator;
    public bool $isField;

    /**
     * @param mixed  $value
     * @param string $operator
     * @param bool   $isField
     */
    public function __construct($value, string $operator = '=', bool $isField = false)
    {
        $this->value = $value;
        $this->operator = $operator;
        $this->isField = $isField;
    }
}
