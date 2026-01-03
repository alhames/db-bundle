<?php

declare(strict_types=1);

namespace Alhames\DbBundle\Db;

use Alhames\DbBundle\Exception\DbException;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;

class DbManager
{
    protected DbConfig $dbConfig;
    protected array $config;
    protected string $defaultConnection;

    protected ?CacheInterface $cache = null;
    protected ?DbQueryFormatterInterface $queryFormatter = null;
    protected ?LoggerInterface $logger = null;

    /** @var DbConnection[] */
    protected array $connections = [];

    public function __construct(DbConfig $dbConfig, array $config, string $defaultConnection = 'default')
    {
        $this->dbConfig = $dbConfig;
        $this->config = $config;
        $this->defaultConnection = $defaultConnection;
        mysqli_report(MYSQLI_REPORT_STRICT);
    }

    public function setDefaultConnection(string $defaultConnection): DbManager
    {
        $this->defaultConnection = $defaultConnection;

        return $this;
    }

    public function setCache(?CacheInterface $cache = null): DbManager
    {
        $this->cache = $cache;
        foreach ($this->connections as $connection) {
            $connection->setCache($cache);
        }

        return $this;
    }

    public function setQueryFormatter(?DbQueryFormatterInterface $queryFormatter = null): DbManager
    {
        $this->queryFormatter = $queryFormatter;
        foreach ($this->connections as $connection) {
            $connection->setQueryFormatter($queryFormatter);
        }

        return $this;
    }

    public function setLogger(?LoggerInterface $logger = null): DbManager
    {
        $this->logger = $logger;
        foreach ($this->connections as $connection) {
            $connection->setLogger($logger);
        }

        return $this;
    }

    /**
     * @throws DbException
     */
    public function getConfig(string $table): array
    {
        return $this->dbConfig->get($table);
    }

    /**
     * @throws DbException
     */
    public function db(string|DbQuery $alias): DbQuery
    {
        if ($alias instanceof DbQuery) {
            $config = $this->dbConfig->get($alias->getAlias());
        } else {
            $config = $this->dbConfig->get($alias);
        }

        $connection = $this->getConnection($config['connection'] ?? null);

        return new DbQuery($alias, $connection, $this->dbConfig);
    }

    public function getConnection(?string $alias = null): DbConnection
    {
        if (null === $alias) {
            $alias = $this->defaultConnection;
        }

        if (isset($this->connections[$alias])) {
            return $this->connections[$alias];
        }

        $this->connections[$alias] = new DbConnection($this->config[$alias], $alias);
        if (null !== $this->cache) {
            $this->connections[$alias]->setCache($this->cache);
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
