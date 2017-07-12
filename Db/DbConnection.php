<?php

namespace DbBundle\Db;

use DbBundle\Exception\ConnectionException;
use DbBundle\Exception\ExecutionException;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

/**
 * Class Sql.
 */
class DbConnection implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var array */
    protected $config;

    /** @var \mysqli */
    protected $mysqli;

    /** @var CacheItemPoolInterface */
    protected $cacheItemPool;

    /** @var string */
    protected $alias;

    /** @var bool */
    protected $connected;

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
            $this->mysqli = new \mysqli( // todo
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
            throw new ConnectionException($this->alias, $this->mysqli->connect_error, (int) $this->mysqli->connect_errno);
        }

        $this->mysqli->set_charset($this->config['charset']);
        $this->connected = true;

        return $this;
    }

    /**
     * @param CacheItemPoolInterface $cache
     *
     * @return static
     */
    public function setCacheItemPool(CacheItemPoolInterface $cache)
    {
        $this->cacheItemPool = $cache;

        return $this;
    }

    /**
     * @param string      $query
     * @param string|null $cacheKey
     * @param int|null    $cacheTime    Timeout in seconds, must be greater than 0.
     * @param bool        $cacheRebuild
     *
     * @return array
     */
    public function query(string $query, string $cacheKey = null, int $cacheTime = null, bool $cacheRebuild = false): array
    {
        $startTime = microtime(true);
        $result = [];
        $isCached = false;

        if (null !== $this->cacheItemPool && !empty($cacheKey) && $cacheTime > 0) {
            $cacheItem = $this->cacheItemPool->getItem($cacheKey);
            if (!$cacheRebuild && $cacheItem->isHit()) {
                $isCached = true;
                $result = $cacheItem->get();
            }
        }

        if (!$isCached) {
            $ipInfo = sprintf("/* %s */\n", isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'cli'); // todo

            // Connect
            $connectTime = microtime(true);
            $this->connect();
            $connectTime = microtime(true) - $connectTime;

            // Query
            $queryTime = microtime(true);
            $queryResult = $this->mysqli->query($ipInfo.$query);
            $queryTime = microtime(true) - $queryTime;

            // Reconnect
            if (2006 == $this->mysqli->errno) {
                $reconnectTime = microtime(true);
                $this->reconnect();
                $connectTime = microtime(true) - $reconnectTime + $connectTime;

                $queryTimeAfterReconnect = microtime(true);
                $queryResult = $this->mysqli->query($ipInfo.$query);
                $queryTime = microtime(true) - $queryTimeAfterReconnect + $queryTime;
            }

            if ($queryResult instanceof \mysqli_result) {
                if ($queryResult->num_rows) {
                    while ($row = $queryResult->fetch_assoc()) {
                        $result[] = $row;
                    }
                }

                $queryResult->free();

                // Save cache
                if (isset($cacheItem)) {
                    $this->cacheItemPool->save(
                        $cacheItem->set($result)->expiresAfter($cacheTime)
                    );
                }
            } elseif (true !== $queryResult) {
                throw new ExecutionException($this->alias, $this->mysqli->error, (int) $this->mysqli->errno, null, $query);
            }
        }

        if (null !== $this->logger) {
            $this->logger->debug($query, [
                'alias' => $this->alias,
                'is_cached' => $isCached,
                'started_at' => $startTime,
                'connect_time' => $connectTime ?? 0,
                'query_time' => $queryTime ?? 0,
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
            throw new ExecutionException($this->alias, $this->mysqli->error, (int) $this->mysqli->errno, null, 'AUTOCOMMIT=0');
        }

        if (!$this->mysqli->begin_transaction()) {
            throw new ExecutionException($this->alias, $this->mysqli->error, (int) $this->mysqli->errno, null, 'BEGIN TRANSACTION');
        }
    }

    /**
     * @throws ExecutionException
     */
    public function commit()
    {
        $this->connect();

        if (!$this->mysqli->commit()) {
            throw new ExecutionException($this->alias, $this->mysqli->error, (int) $this->mysqli->errno, null, 'COMMIT');
        }

        if (!$this->mysqli->autocommit(true)) {
            throw new ExecutionException($this->alias, $this->mysqli->error, (int) $this->mysqli->errno, null, 'AUTOCOMMIT=1');
        }
    }

    /**
     * @throws ExecutionException
     */
    public function rollback()
    {
        $this->connect();

        if (!$this->mysqli->rollback()) {
            throw new ExecutionException($this->alias, $this->mysqli->error, (int) $this->mysqli->errno, null, 'ROLLBACK');
        }

        if (!$this->mysqli->autocommit(true)) {
            throw new ExecutionException($this->alias, $this->mysqli->error, (int) $this->mysqli->errno, null, 'AUTOCOMMIT=1');
        }
    }
}
