<?php

namespace Alhames\DbBundle\Db;

use Alhames\DbBundle\Exception\DbException;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Contracts\Cache\CacheInterface;

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

    /** @var CacheInterface */
    protected $cache;

    /** @var DbQueryFormatterInterface */
    protected $queryFormatter;

    /** @var DbConnection[] */
    protected $connections = [];

    /**
     * @param DbConfig                       $dbConfig
     * @param array                          $config
     * @param string                         $defaultConnection
     * @param CacheInterface|null            $cache
     * @param DbQueryFormatterInterface|null $queryFormatter
     */
    public function __construct(DbConfig $dbConfig, array $config, string $defaultConnection = 'default', ?CacheInterface $cache = null, ?DbQueryFormatterInterface $queryFormatter = null)
    {
        $this->dbConfig = $dbConfig;
        $this->config = $config;
        $this->defaultConnection = $defaultConnection;
        mysqli_report(MYSQLI_REPORT_STRICT);

        $this->cache = $cache;
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
