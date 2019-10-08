<?php

namespace Alhames\DbBundle\Exception;

/**
 * Class SqlException.
 */
abstract class AbstractException extends \RuntimeException
{
    /** @var string */
    protected $alias;

    /**
     * @param string|null     $alias
     * @param string|null     $message
     * @param int             $code
     * @param \Throwable|null $previous
     */
    public function __construct(?string $alias = null, ?string $message = null, int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $this->alias = $alias;
    }

    /**
     * @return string|null
     */
    public function getAlias(): ?string
    {
        return $this->alias;
    }
}
