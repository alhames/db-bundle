<?php

namespace DbBundle\Exception;

/**
 * Class SqlException.
 */
abstract class AbstractException extends \RuntimeException
{
    /** @var string */
    protected $alias;

    /**
     * @param string          $alias
     * @param string|null     $message
     * @param int             $code
     * @param \Exception|null $previous
     */
    public function __construct(string $alias, string $message = null, int $code = 0, \Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $this->alias = $alias;
    }

    /**
     * @return string
     */
    public function getAlias(): string
    {
        return $this->alias;
    }
}
