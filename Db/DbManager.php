<?php

namespace Alhames\DbBundle\Db;

use Alhames\DbBundle\Exception\DbException;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

/**
 * Class DbManager.
 */
class DbManager implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var DbConfig */
    protected $dbConfig;

    /** @var array */
    protected $config;

    /** @var string */
    protected $defaultConnection;

    /** @var CacheItemPoolInterface */
    protected $cacheItemPool;

    /** @var DbQueryFormatterInterface */
    protected $queryFormatter;

    /** @var DbConnection[] */
    protected $connections = [];

    /**
     * @param DbConfig                       $dbConfig
     * @param array                          $config
     * @param string                         $defaultConnection
     * @param CacheItemPoolInterface|null    $cacheItemPool
     * @param DbQueryFormatterInterface|null $queryFormatter
     */
    public function __construct(DbConfig $dbConfig, array $config, string $defaultConnection = 'default', CacheItemPoolInterface $cacheItemPool = null, DbQueryFormatterInterface $queryFormatter = null)
    {
        $this->dbConfig = $dbConfig;
        $this->config = $config;
        $this->defaultConnection = $defaultConnection;
        mysqli_report(MYSQLI_REPORT_STRICT);

        $this->cacheItemPool = $cacheItemPool;
        $this->queryFormatter = $queryFormatter;
    }

    /**
     * @param string $defaultConnection
     *
     * @return static
     */
    public function setDefaultConnection(string $defaultConnection)
    {
        $this->defaultConnection = $defaultConnection;

        return $this;
    }

    /**
     * @param string $table
     *
     * @throws DbException
     *
     * @return array
     */
    public function getConfig(string $table): array
    {
        return $this->dbConfig->get($table);
    }

    /**
     * @param string|DbQuery $alias
     *
     * @throws DbException
     *
     * @return DbQuery
     */
    public function db($alias): DbQuery
    {
        if ($alias instanceof DbQuery) {
            $config = $this->dbConfig->get($alias->getAlias());
        } else {
            $config = $this->dbConfig->get($alias);
        }

        $connection = $this->getConnection($config['connection'] ?? null);

        return new DbQuery($alias, $connection, $this->dbConfig);
    }

    /**
     * @param string $alias
     *
     * @return DbConnection
     */
    public function getConnection(string $alias = null): DbConnection
    {
        if (null === $alias) {
            $alias = $this->defaultConnection;
        }

        if (isset($this->connections[$alias])) {
            return $this->connections[$alias];
        }

        $this->connections[$alias] = new DbConnection($this->config[$alias], $alias);
        if (null !== $this->cacheItemPool) {
            $this->connections[$alias]->setCacheItemPool($this->cacheItemPool);
        }
        if (null !== $this->logger) {
            $this->connections[$alias]->setLogger($this->logger);
        }
        if (null !== $this->queryFormatter) {
            $this->connections[$alias]->setQueryFormatter($this->queryFormatter);
        }

        return $this->connections[$alias];
    }
}
