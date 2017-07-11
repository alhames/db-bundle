<?php

namespace DbBundle\Db;

/**
 * Class DbValue.
 */
class DbValue
{
    /** @var mixed */
    public $value;

    /** @var string */
    public $operator;

    /** @var bool */
    public $isField;

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
