<?php

declare(strict_types=1);

namespace Alhames\DbBundle\Exception;

class ExecutionException extends AbstractException
{
    protected ?string $query;

    public function __construct(string $alias, ?string $message = null, int $code = 0, ?\Throwable $previous = null, ?string $query = null)
    {
        parent::__construct($alias, $message, $code, $previous);
        $this->query = $query;
    }

    public function getQuery(): ?string
    {
        return $this->query;
    }
}
