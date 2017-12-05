<?php

namespace Alhames\DbBundle\DataCollector;

use Psr\Log\AbstractLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollectorInterface;

/**
 * Class DbDataCollector.
 */
class DbDataCollector extends AbstractLogger implements DataCollectorInterface
{
    /** @var array */
    protected $data = [];

    /**
     * {@inheritdoc}
     */
    public function log($level, $message, array $context = [])
    {
        $this->data[] = array_merge($context, ['query' => $message, 'level' => $level]);
    }

    /**
     * {@inheritdoc}
     */
    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'db';
    }

    /**
     * @return float
     */
    public function getTime(): float
    {
        return array_sum(array_column($this->data, 'total_time'));
    }

    /**
     * @return int
     */
    public function getCount(): int
    {
        return count($this->data);
    }

    /**
     * @return int
     */
    public function getCacheHitsCount(): int
    {
        return array_sum(array_column($this->data, 'is_cached'));
    }

    /**
     * @return array
     */
    public function getQueries(): array
    {
        return $this->data;
    }

    /**
     * {@inheritdoc}
     */
    public function reset()
    {
        $this->data = [];
    }
}
