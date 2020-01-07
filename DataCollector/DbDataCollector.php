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
        // disabled for console requests
        if ('cli' === PHP_SAPI) {
            return;
        }
        $this->data[] = array_merge($context, ['query' => $message, 'level' => $level]);
    }

    /**
     * {@inheritdoc}
     */
    public function collect(Request $request, Response $response, \Throwable $exception = null)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'alhames_db';
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
        $pattern = "#(SELECT|FROM|INSERT|UPDATE|SET|DELETE|REPLACE|TRUNCATE|INNER|LEFT|JOIN|WHERE|LIMIT|ORDER BY|GROUP BY|AND|OR|IS|ASC|DESC|AS|NOT|IN|ON|DISTINCT)#";
        foreach ($this->data as &$item) {
            $formattedQuery = htmlspecialchars($item['query']);
            $formattedQuery = preg_replace($pattern, '<span class="keyword">$1</span>', $formattedQuery);
            $formattedQuery = '<span class="query-row">'.implode('</span><br><span class="query-row">', explode("\n", $formattedQuery)).'</span>';
            $item['formatted'] = $formattedQuery;
        }
        unset($item);

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
