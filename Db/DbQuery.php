<?php

namespace Alhames\DbBundle\Db;

use Alhames\DbBundle\Exception\DbException;

class DbQuery
{
    protected string $alias;
    protected DbConnection $connection;
    protected DbConfig $config;
    protected ?DbQuery $subQuery = null;
    protected bool $securityEnable = true;

    protected string $method = '';
    protected ?string $options = null;
    protected ?string $fields = null;
    protected array $join = [];
    protected array $index = [];
    protected ?string $where = null;
    protected ?string $groupBy = null;
    protected ?string $orderBy = null;
    protected ?string $having = null;
    protected ?int $limit = null;
    protected ?int $offset = null;
    protected ?string $set = null;
    protected ?string $insert = null;
    protected ?string $onDuplicate = null;

    protected ?string $cacheKey = null;
    protected ?int $cacheTime = null;
    protected bool $cacheRebuild = false;

    /** @var array|bool */
    protected $result;
    protected int $rowCount;

    /**
     * @param string|DbQuery $alias
     */
    public function __construct($alias, DbConnection $connection, DbConfig $config)
    {
        $this->connection = $connection;
        $this->config = $config;

        if ($alias instanceof self) {
            $this->subQuery = $alias;
            $this->alias = $alias->getAlias();
        } else {
            $this->alias = $alias;
        }
    }

    public function getAlias(): string
    {
        return $this->alias;
    }

    public function getTable(): string
    {
        return $this->config->getTable($this->alias);
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function beginTransaction(): void
    {
        $this->connection->beginTransaction();
    }

    public function commit(): void
    {
        $this->connection->commit();
    }

    public function rollback(): void
    {
        $this->connection->rollback();
    }

    /**
     * @param array|string $fields
     */
    public function select($fields = null, ?string $options = null): DbQuery
    {
        $this->method = 'select';
        $this->options = $options;
        $this->fields = $this->prepareFields($fields);

        return $this;
    }

    public function insert(array $data, ?string $options = null): DbQuery
    {
        $this->options = $options;

        if (isset($data[0]) && is_array($data[0])) {
            $this->method = 'multi_insert';
            $this->insert = $this->prepareMultiInsertStatement($data);
        } else {
            $this->method = 'insert';
            $this->set = $this->prepareSetStatement($data);
        }

        return $this;
    }

    public function update(array $data, ?string $options = null): DbQuery
    {
        $this->method = 'update';
        $this->options = $options;
        $this->set = $this->prepareSetStatement($data);

        return $this;
    }

    public function replace(array $data, ?string $options = null): DbQuery
    {
        $this->method = 'replace';
        $this->options = $options;
        $this->set = $this->prepareSetStatement($data);

        return $this;
    }

    public function delete(?string $options = null): DbQuery
    {
        $this->method = 'delete';
        $this->options = $options;

        return $this;
    }

    public function truncate(): DbQuery
    {
        $this->method = 'truncate';

        return $this;
    }

    public function optimize(): DbQuery
    {
        $this->method = 'optimize';

        return $this;
    }

    public function disableSecurity(): DbQuery
    {
        $this->securityEnable = false;

        return $this;
    }

    /**
     * @param string $type Allow types: INNER (default), LEFT, RIGHT
     *
     * @throws DbException
     */
    public function join(string $table, string $alias, array $relationStatement, string $type = 'INNER'): DbQuery
    {
        if (!in_array($type, ['INNER', 'LEFT', 'RIGHT'], true)) {
            throw new DbException($this->connection->getAlias(), sprintf('Invalid join type "%s"', $type));
        }

        $relationStatement = $this->prepareWhereStatement($relationStatement);
        $this->join[] = $type.' JOIN '.$this->config->getTable($table).' AS `'.$alias.'` ON ('.$relationStatement.')';

        return $this;
    }

    /**
     * @param string|array $indexList Index list
     * @param string       $action    Allow values: USE (default), FORCE, IGNORE
     * @param string|null  $purpose   Allow values: JOIN, ORDER BY, GROUP BY, empty (default)
     *
     * @throws DbException
     */
    public function index($indexList, string $action = 'USE', ?string $purpose = null): DbQuery
    {
        if (!in_array($action, ['USE', 'FORCE', 'IGNORE'], true)) {
            throw new DbException($this->connection->getAlias(), sprintf('Invalid action "%s"', $action));
        }

        if (!in_array($purpose, [null, 'JOIN', 'ORDER BY', 'GROUP BY'], true)) {
            throw new DbException($this->connection->getAlias(), sprintf('Invalid purpose "%s"', $purpose));
        }

        $indexList = is_array($indexList) ? $indexList : [$indexList];
        foreach ($indexList as &$index) {
            $index = '`'.trim($index, '` ').'`';
        }
        unset($index);

        $this->index[] = $action.' INDEX'
            .(null !== $purpose ? ' FOR '.$purpose : '')
            .' ('.implode(', ', $indexList).')';

        return $this;
    }

    public function where(?array $params = null, ?string $statement = null): DbQuery
    {
        if (null === $params) {
            $this->where = $statement;
        } else {
            $this->where = $this->prepareWhereStatement($params);
        }

        return $this;
    }

    /**
     * @param string|array|null $options
     */
    public function groupBy($options): DbQuery
    {
        $this->groupBy = $this->prepareOrderByStatement($options);

        return $this;
    }

    public function having(?array $params = null, ?string $statement = null): DbQuery
    {
        if (null === $params) {
            $this->having = $statement;
        } else {
            $this->having = $this->prepareWhereStatement($params);
        }

        return $this;
    }

    /**
     * @param string|array $options
     */
    public function orderBy($options): DbQuery
    {
        $this->orderBy = $this->prepareOrderByStatement($options);

        return $this;
    }

    public function limit(?int $limit = null): DbQuery
    {
        $this->limit = $limit;

        return $this;
    }

    public function offset(?int $offset = null): DbQuery
    {
        $this->offset = $offset;

        return $this;
    }

    public function setPage(int $page, int $count): DbQuery
    {
        $this->offset = ($page - 1) * $count;
        $this->limit = $count;

        return $this;
    }

    public function onDuplicateKey(array $data): DbQuery
    {
        $this->onDuplicate = $this->prepareSetStatement($data);

        return $this;
    }

    /**
     * @throws DbException
     */
    public function setCaching(string $key, int $time, bool $isRebuild = false): DbQuery
    {
        if ('select' !== $this->method) {
            throw new DbException($this->connection->getAlias(), 'Cache available only for SELECT');
        }

        $this->cacheKey = $key;
        $this->cacheTime = $time;
        $this->cacheRebuild = $isRebuild;

        return $this;
    }

    public function getRows(?string $key = null, ?string $field = null, bool $isGroup = false): array
    {
        $this->exec();

        if (null === $key) {
            if (null === $field) {
                $data = $this->result;
            } else {
                $data = array_column($this->result, $field);
            }
        } elseif ($isGroup) {
            $data = [];

            foreach ($this->result as $item) {
                if (!isset($item[$key])) {
                    continue;
                }

                $k = $item[$key];
                if (!isset($data[$k])) {
                    $data[$k] = [];
                }

                $data[$k][] = null !== $field ? $item[$field] : $item;
            }
        } else {
            $data = array_combine(
                array_column($this->result, $key),
                null === $field ? $this->result : array_column($this->result, $field)
            );
        }

        return $data;
    }

    /**
     * @return bool|array|string|null
     */
    public function getRow(string $field = null)
    {
        $this->exec();

        if (empty($this->result)) {
            return false;
        }

        if (null === $field) {
            return $this->result[0];
        }

        return $this->result[0][$field];
    }

    public function getRowCount(): ?int
    {
        $this->exec();

        if (null === $this->rowCount) {
            $this->rowCount = $this->connection->getAffectedRows();
        }

        return $this->rowCount;
    }

    public function getPageCount(): int
    {
        return (int) ceil($this->getRowCount() / $this->limit);
    }

    public function getInsertId(): int
    {
        $this->exec();

        return $this->connection->getInsertId();
    }

    public function exec(): void
    {
        if (null !== $this->result) {
            return;
        }

        $this->result = $this->connection->query($this->getQuery(), $this->cacheKey, $this->cacheTime, $this->cacheRebuild);
        if ('select' === $this->method && false !== strpos($this->options, Db::CALC_FOUND_ROWS)) {
            $this->rowCount = $this->connection->getFoundRows();
        }
    }

    /**
     * @throws DbException
     */
    public function getQuery(): string
    {
        if (
            null !== $this->subQuery
            && ('select' !== $this->method || 'select' !== $this->subQuery->getMethod())
        ) {
            throw new DbException($this->connection->getAlias(), 'Sub query is supported only in SELECT-queries');
        }

        switch ($this->method) {
            case 'select':

                $query = 'SELECT ';

                if (!empty($this->options)) {
                    $query .= $this->options.' ';
                }

                $query .= $this->fields.PHP_EOL.'FROM ';

                if (null !== $this->subQuery) {
                    $query .= '('.PHP_EOL.$this->subQuery->getQuery().PHP_EOL.')';
                } else {
                    $query .= $this->getTable();
                }
                $query .= ' AS self';

                if (!empty($this->index)) {
                    $query .= PHP_EOL.implode(PHP_EOL, $this->index);
                }

                if (!empty($this->join)) {
                    $query .= PHP_EOL.implode(PHP_EOL, $this->join);
                }

                if (!empty($this->where)) {
                    $query .= PHP_EOL.'WHERE '.$this->where;
                }

                if (!empty($this->groupBy)) {
                    $query .= PHP_EOL.'GROUP BY '.$this->groupBy;
                }

                if (!empty($this->having)) {
                    $query .= PHP_EOL.'HAVING '.$this->having;
                }

                if (!empty($this->orderBy)) {
                    $query .= PHP_EOL.'ORDER BY '.$this->orderBy;
                }

                if (!empty($this->limit)) {
                    $query .= PHP_EOL.'LIMIT '.$this->limit;
                }

                if (!empty($this->offset)) {
                    $query .= PHP_EOL.'OFFSET '.$this->offset;
                }

                return $query;

            case 'insert':

                $query = 'INSERT ';

                if (!empty($this->options)) {
                    $query .= $this->options.' ';
                }

                $query .= 'INTO '.$this->getTable().PHP_EOL.'SET '.$this->set;

                if (!empty($this->onDuplicate)) {
                    $query .= PHP_EOL.'ON DUPLICATE KEY UPDATE '.$this->onDuplicate;
                }

                return $query;

            case 'multi_insert':

                $query = 'INSERT ';

                if (!empty($this->options)) {
                    $query .= $this->options.' ';
                }

                $query .= 'INTO '.$this->getTable().PHP_EOL.$this->insert;

                if (!empty($this->onDuplicate)) {
                    $query .= PHP_EOL.'ON DUPLICATE KEY UPDATE '.$this->onDuplicate;
                }

                return $query;

            case 'update':

                $query = 'UPDATE ';

                if (!empty($this->options)) {
                    $query .= $this->options.' ';
                }

                $query .= $this->getTable().PHP_EOL.'SET '.$this->set;

                if (!empty($this->where)) {
                    $query .= PHP_EOL.'WHERE '.$this->where;
                } elseif ($this->securityEnable) {
                    throw new DbException($this->connection->getAlias(), 'Operation UPDATE require non-empty WHERE');
                }

                if (!empty($this->orderBy)) {
                    $query .= PHP_EOL.'ORDER BY '.$this->orderBy;
                }

                if (!empty($this->limit)) {
                    $query .= PHP_EOL.'LIMIT '.$this->limit;
                }

                if (!empty($this->offset)) {
                    $query .= PHP_EOL.'OFFSET '.$this->offset;
                }

                return $query;

            case 'replace':

                $query = 'REPLACE ';

                if (!empty($this->options)) {
                    $query .= $this->options.' ';
                }

                $query .= 'INTO '.$this->getTable().PHP_EOL.'SET '.$this->set;

                return $query;

            case 'delete':

                $query = 'DELETE ';

                if (!empty($this->options)) {
                    $query .= $this->options.' ';
                }

                $query .= 'FROM '.$this->getTable();

                if (!empty($this->where)) {
                    $query .= PHP_EOL.'WHERE '.$this->where;
                } elseif ($this->securityEnable) {
                    throw new DbException($this->connection->getAlias(), 'Operation DELETE require non-empty WHERE');
                }

                if (!empty($this->orderBy)) {
                    $query .= PHP_EOL.'ORDER BY '.$this->orderBy;
                }

                if (!empty($this->limit)) {
                    $query .= PHP_EOL.'LIMIT '.$this->limit;
                }

                if (!empty($this->offset)) {
                    $query .= PHP_EOL.'OFFSET '.$this->offset;
                }

                return $query;

            case 'truncate':

                if ($this->securityEnable) {
                    throw new DbException($this->connection->getAlias(), 'Operation TRUNCATE denied');
                }

                return 'TRUNCATE TABLE '.$this->getTable();

            case 'optimize':

                return 'OPTIMIZE TABLE '.$this->getTable();

            default:
                throw new DbException($this->connection->getAlias(), 'Unknown operation');
        }
    }

    public function __toString(): string
    {
        return $this->getQuery();
    }

    /**
     * @throws DbException
     */
    protected function prepareFieldStatement(string $name, string $operator = '='): string
    {
        if (!in_array($operator, ['<', '>', '=', '!=', '>=', '<='], true)) {
            throw new DbException($this->connection->getAlias(), 'Invalid operator');
        }

        return $operator.' '.$this->prepareField($name);
    }

    /**
     * @param mixed $value
     *
     * @throws DbException
     */
    protected function prepareValueStatement($value, string $operator = '='): string
    {
        if (null === $value) {
            if ('=' === $operator || 'IS' === $operator) {
                return 'IS NULL';
            } elseif ('!=' === $operator || 'IS NOT' === $operator) {
                return 'IS NOT NULL';
            }

            throw new DbException($this->connection->getAlias(), 'Invalid operator for NULL');
        }

        if (is_array($value)) {
            if ('BETWEEN' === $operator) {
                return 'BETWEEN '.$this->prepareValue($value[0]).' AND '.$this->prepareValue($value[1]);
            }

            if ('=' === $operator) {
                $operator = 'IN';
            } elseif ('!=' === $operator) {
                $operator = 'NOT IN';
            } elseif ('IN' !== $operator && 'NOT IN' !== $operator) {
                throw new DbException($this->connection->getAlias(), 'Invalid operator for IN()');
            }

            $list = [];
            foreach ($value as $item) {
                $list[] = $this->prepareValue($item);
            }

            return $operator.' ('.implode(',', $list).')';
        }

        if (!in_array($operator, ['LIKE', '<', '>', '=', '!=', '>=', '<='], true)) {
            throw new DbException($this->connection->getAlias(), 'Invalid operator');
        }

        return $operator.' '.$this->prepareValue($value);
    }

    protected function prepareFields($fields = null): string
    {
        if (null === $fields || '*' === $fields) {
            return '*';
        } elseif (is_array($fields)) {
            $fieldsArray = [];

            if (isset($fields[0]) && '*' === $fields[0]) {
                $fieldsArray[] = array_shift($fields);
            }

            foreach ($fields as $key => $value) {
                if (is_string($key)) {
                    $fieldsArray[] = $this->prepareField($key).' AS `'.$value.'`';
                } else {
                    $fieldsArray[] = $this->prepareField($value);
                }
            }

            return implode(', ', $fieldsArray);
        } elseif (false === strpos($fields, ',')) {
            return $this->prepareField($fields);
        }

        return $fields;
    }

    protected function prepareField(string $name): string
    {
        $name = trim($name);

        if (false === strpos($name, '.') && false === strpos($name, '(') && false === strpos($name, ' ')) {
            $name = '`'.trim($name, '`').'`';
        }

        return $name;
    }

    /**
     * @param mixed $value
     *
     * @throws DbException
     */
    protected function prepareValue($value): string
    {
        if (is_int($value)) {
            return (string) $value;
        } elseif (is_float($value)) {
            return str_replace(',', '.', strval($value)); // Fix for windows
        } elseif (is_string($value)) {
            return '"'.$this->connection->escape($value).'"';
        } elseif (is_bool($value)) {
            return $value ? '1' : '0';
        } elseif (null === $value) {
            return 'NULL';
        } elseif ($value instanceof \DateTimeInterface) {
            return $value->format('"Y-m-d H:i:s"');
        }

        throw new DbException($this->connection->getAlias(), 'Invalid argument');
    }

    protected function prepareWhereStatement(array $params): string
    {
        $parts = []; // todo

        foreach ($params as $field => $param) {
            if ($param instanceof DbValue) {
                if ($param->isField) {
                    $parts[] = $this->prepareField($field).' '.$this->prepareFieldStatement($param->value, $param->operator);
                } else {
                    $parts[] = $this->prepareField($field).' '.$this->prepareValueStatement($param->value, $param->operator);
                }
            } else {
                $parts[] = $this->prepareField($field).' '.$this->prepareValueStatement($param);
            }
        }

        return implode(' AND ', $parts);
    }

    /**
     * @param array|string|null $options
     */
    protected function prepareOrderByStatement($options): ?string
    {
        if (is_array($options)) {
            $optionsArray = [];
            foreach ($options as $optionKey => $optionValue) {
                if (is_string($optionKey)) {
                    $optionsArray[] = $this->prepareField($optionKey).' '.$optionValue;
                } else {
                    $optionsArray[] = $this->prepareField($optionValue);
                }
            }

            $options = implode(', ', $optionsArray);
        }

        return $options;
    }

    protected function prepareSetStatement(array $params): string
    {
        $data = [];
        foreach ($params as $field => $param) {
            if ($param instanceof DbValue) {
                if ($param->isField) {
                    $data[] = $this->prepareField($field).' = '.$this->prepareField($param->value);
                } else {
                    $data[] = $this->prepareField($field).' = '.$this->prepareValue($param->value);
                }
            } else {
                $data[] = $this->prepareField($field).' = '.$this->prepareValue($param);
            }
        }

        return implode(', ', $data);
    }

    protected function prepareMultiInsertStatement(array $params): string
    {
        $fields = array_keys($params[0]);

        foreach ($fields as &$field) {
            $field = $this->prepareField($field);
        }
        unset($field);

        $statement = '('.implode(',', $fields).') VALUES';

        foreach ($params as $row) {
            $rowValues = [];
            foreach ($row as $value) {
                if ($value instanceof DbValue) {
                    if ($value->isField) {
                        $rowValues[] = $this->prepareField($value->value);
                    } else {
                        $rowValues[] = $this->prepareValue($value->value);
                    }
                } else {
                    $rowValues[] = $this->prepareValue($value);
                }
            }
            $statement .= PHP_EOL.'('.implode(',', $rowValues).'),';
        }

        return rtrim($statement, ',');
    }
}
