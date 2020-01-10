<?php

namespace Alhames\DbBundle\Db;

use Alhames\DbBundle\Exception\ConnectionException;
use Alhames\DbBundle\Exception\ExecutionException;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Class Sql.
 */
class DbConnection
{
    const CR_SERVER_GONE_ERROR = 2006;

    /** @var array */
    protected $config;

    /** @var \mysqli */
    protected $mysqli;

    /** @var CacheInterface */
    protected $cache;

    /** @var string */
    protected $alias;

    /** @var bool */
    protected $connected;

    /** @var DbQueryFormatterInterface */
    protected $queryFormatter;

    /** @var LoggerInterface */
    protected $logger;

    /**
     * @param array  $config
     * @param string $alias
     */
    public function __construct(array $config, string $alias = 'default')
    {
        $this->config = $config;
        $this->alias = $alias;
        $this->connected = false;
    }

    /**
     * @return string
     */
    public function getAlias(): string
    {
        return $this->alias;
    }

    /**
     * @throws ConnectionException
     *
     * @return static
     */
    public function connect()
    {
        if ($this->connected) {
            return $this;
        }

        try {
            $this->mysqli = new \mysqli(
                $this->config['host'],
                $this->config['username'],
                $this->config['password'],
                $this->config['database'] ?? '',
                $this->config['port'] ?? 3306
            );
        } catch (\Throwable $e) {
            throw new ConnectionException($this->alias, $e->getMessage(), $e->getCode(), $e);
        }

        if ($this->mysqli->connect_errno) {
            throw new ConnectionException($this->alias, $this->mysqli->connect_error, $this->mysqli->connect_errno);
        }

        try {
            $successSet = $this->mysqli->set_charset($this->config['charset']);
            if (!empty($this->config['timezone'])) {
                $successSet = $successSet && $this->mysqli->query(sprintf('SET time_zone = "%s"', $this->config['timezone']));
            }
        } catch (\Throwable $e) {
            throw $this->createException(null, $e);
        }

        if (!$successSet) {
            throw $this->createException();
        }

        $this->connected = true;

        return $this;
    }

    /**
     * @param CacheInterface|null $cache
     *
     * @return static
     */
    public function setCache(?CacheInterface $cache = null)
    {
        $this->cache = $cache;

        return $this;
    }

    /**
     * @param DbQueryFormatterInterface|null $queryFormatter
     *
     * @return static
     */
    public function setQueryFormatter(?DbQueryFormatterInterface $queryFormatter = null)
    {
        $this->queryFormatter = $queryFormatter;

        return $this;
    }

    /**
     * @param LoggerInterface|null $logger
     *
     * @return static
     */
    public function setLogger(?LoggerInterface $logger = null)
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * @param string      $query
     * @param string|null $cacheKey
     * @param int|null    $cacheTime Timeout in seconds, must be greater than 0.
     * @param bool        $cacheRebuild
     *
     * @return array
     */
    public function query(string $query, ?string $cacheKey = null, ?int $cacheTime = null, bool $cacheRebuild = false): array
    {
        $startTime = microtime(true);
        $isCached = false;
        $connectTime = 0;
        $queryTime = 0;

        if (null !== $this->cache && !empty($cacheKey) && $cacheTime > 0) {
            if ($cacheRebuild) {
                $this->cache->delete($cacheKey);
            }
            $isCached = true;
            $result = $this->cache->get($cacheKey, function (ItemInterface $item) use ($query, $cacheKey, $cacheTime, &$connectTime, &$queryTime) {
                $item->expiresAfter($cacheTime);
                [$result, $connectTime, $queryTime] = $this->doQuery($query, $cacheKey, $cacheTime);

                return $result;
            });
        } else {
            [$result, $connectTime, $queryTime] = $this->doQuery($query, $cacheKey, $cacheTime);
        }

        if (null !== $this->logger) {
            $this->logger->debug($query, [
                'alias' => $this->alias,
                'is_cached' => $isCached,
                'started_at' => $startTime,
                'connect_time' => $connectTime,
                'query_time' => $queryTime,
                'total_time' => microtime(true) - $startTime,
            ]);
        }

        return $result;
    }

    /**
     * @param string $string
     *
     * @return string
     */
    public function escape(string $string): string
    {
        return $this->connect()->mysqli->escape_string($string);
    }

    /**
     * @param string $string
     *
     * @return string
     */
    public function escapeLike(string $string): string
    {
        return Db::escapeLike($string);
    }

    /**
     * @return int
     */
    public function getInsertId(): int
    {
        return (int) $this->connect()->mysqli->insert_id;
    }

    /**
     * @return int
     */
    public function getAffectedRows(): int
    {
        return (int) $this->connect()->mysqli->affected_rows;
    }

    /**
     * @return int
     */
    public function getFoundRows(): int
    {
        $result = $this->connect()->mysqli->query('SELECT FOUND_ROWS()');
        $countResult = $result->fetch_row();
        $result->free();

        return (int) $countResult[0];
    }

    /**
     * @return static
     */
    public function close()
    {
        if ($this->connected) {
            $this->mysqli->close();
        }

        $this->mysqli = null;
        $this->connected = false;

        return $this;
    }

    /**
     * @return static
     */
    public function reconnect()
    {
        return $this->close()->connect();
    }

    /**
     * @throws ExecutionException
     */
    public function beginTransaction()
    {
        $this->connect();

        if (!$this->mysqli->autocommit(false)) {
            throw $this->createException('AUTOCOMMIT=0');
        }

        if (!$this->mysqli->begin_transaction()) {
            throw $this->createException('BEGIN TRANSACTION');
        }
    }

    /**
     * @throws ExecutionException
     */
    public function commit()
    {
        $this->connect();

        if (!$this->mysqli->commit()) {
            throw $this->createException('COMMIT');
        }

        if (!$this->mysqli->autocommit(true)) {
            throw $this->createException('AUTOCOMMIT=1');
        }
    }

    /**
     * @throws ExecutionException
     */
    public function rollback()
    {
        $this->connect();

        if (!$this->mysqli->rollback()) {
            throw $this->createException('ROLLBACK');
        }

        if (!$this->mysqli->autocommit(true)) {
            throw $this->createException('AUTOCOMMIT=1');
        }
    }

    /**
     * @param string      $query
     * @param string|null $cacheKey
     * @param int|null    $cacheTime
     *
     * @return array
     */
    protected function doQuery(string $query, ?string $cacheKey = null, ?int $cacheTime = null): array
    {
        $formattedQuery = null !== $this->queryFormatter ? $this->queryFormatter->format($query, $cacheKey, $cacheTime) : $query;
        $result = [];

        // Connect
        $connectTime = microtime(true);
        $this->connect();
        $connectTime = microtime(true) - $connectTime;

        // Query
        $queryTime = microtime(true);
        try {
            $queryResult = $this->mysqli->query($formattedQuery);
        } catch (\Throwable $e) {
            if (self::CR_SERVER_GONE_ERROR !== $this->mysqli->errno) {
                throw $this->createException($query, $e);
            }
        }
        $queryTime = microtime(true) - $queryTime;

        // Reconnect
        if (self::CR_SERVER_GONE_ERROR === $this->mysqli->errno) {
            $reconnectTime = microtime(true);
            $this->reconnect();
            $connectTime = microtime(true) - $reconnectTime + $connectTime;

            $queryTimeAfterReconnect = microtime(true);
            try {
                $queryResult = $this->mysqli->query($formattedQuery);
            } catch (\Throwable $e) {
                throw $this->createException($query, $e);
            }
            $queryTime = microtime(true) - $queryTimeAfterReconnect + $queryTime;
        }

        if ($queryResult instanceof \mysqli_result) {
            if ($queryResult->num_rows) {
                while ($row = $queryResult->fetch_assoc()) {
                    $result[] = $row;
                }
            }

            $queryResult->free();
        } elseif (true !== $queryResult) {
            throw $this->createException($query);
        }

        return [$result, $connectTime, $queryTime];
    }

    /**
     * @param string|null     $query
     * @param \Throwable|null $exception
     *
     * @return ExecutionException
     */
    protected function createException(?string $query = null, ?\Throwable $exception = null): ExecutionException
    {
        return new ExecutionException($this->alias, $this->mysqli->error, $this->mysqli->errno, $exception, $query);
    }
}
