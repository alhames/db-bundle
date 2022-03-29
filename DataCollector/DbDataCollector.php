<?php

namespace Alhames\DbBundle\DataCollector;

use Psr\Log\AbstractLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollectorInterface;

class DbDataCollector extends AbstractLogger implements DataCollectorInterface
{
    protected array $data = [];

    public function log($level, $message, array $context = []): void
    {
        // disabled for console requests
        if ('cli' === PHP_SAPI) {
            return;
        }
        $this->data[] = array_merge($context, ['query' => $message, 'level' => $level]);
    }

    public function collect(Request $request, Response $response, \Throwable $exception = null)
    {
    }

    public function getName(): string
    {
        return 'alhames_db';
    }

    public function getTime(): float
    {
        return array_sum(array_column($this->data, 'total_time'));
    }

    public function getCount(): int
    {
        return count($this->data);
    }

    public function getCacheHitsCount(): int
    {
        return array_sum(array_column($this->data, 'is_cached'));
    }

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

    public function reset()
    {
        $this->data = [];
    }
}
