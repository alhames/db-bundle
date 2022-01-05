<?php

namespace Alhames\DbBundle\Exception;

abstract class AbstractException extends \RuntimeException
{
    protected ?string $alias;

    public function __construct(?string $alias = null, ?string $message = null, int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->alias = $alias;
    }

    public function getAlias(): ?string
    {
        return $this->alias;
    }
}
