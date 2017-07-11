<?php

namespace DbBundle\Exception;

/**
 * Class ExecutionException.
 */
class ExecutionException extends AbstractException
{
    /** @var string */
    protected $query;

    /**
     * @param string          $alias
     * @param string|null     $message
     * @param int             $code
     * @param \Exception|null $previous
     * @param string|null     $query
     */
    public function __construct(string $alias, string $message = null, int $code = 0, \Exception $previous = null, string $query = null)
    {
        parent::__construct($alias, $message, $code, $previous);

        $this->query = $query;
    }

    /**
     * @return string
     */
    public function getQuery(): string
    {
        return $this->query;
    }
}
