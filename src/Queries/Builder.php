<?php

namespace Wilkques\Database\Queries;

use Wilkques\Database\Connections\ConnectionInterface;
use Wilkques\Database\Queries\Grammar\GrammarInterface;
use Wilkques\Database\Queries\Process\ProcessInterface;

class Builder
{
    /** @var array */
    protected static $resolvers = array();
    /** @var array */
    protected $bindData = array();
    /** @var array */
    protected $paginate = array(
        "prePage"       => 10,
        "currentPage"   => 0,
    );

    /**
     * @param ConnectionInterface $connection
     * @param GrammarInterface $grammar
     * @param ProcessInterface $process
     */
    public function __construct(
        ConnectionInterface $connection,
        GrammarInterface $grammar = null,
        ProcessInterface $process = null
    ) {
        static::resolverFor(static::class, $this);

        $this->setConnection($connection)->setGrammar($grammar)->setProcess($process);
    }

    /**
     * @param string $class
     * 
     * @return string
     */
    public static function getResolverByKey($class)
    {
        foreach (static::$resolvers as $key => $abstract) {
            if (in_array($class, class_implements($abstract))) {
                break;
            }

            if (get_class($abstract) === $class) {
                break;
            }
        }

        return $abstract;
    }

    /**
     * @param ConnectionInterface $connection
     * 
     * @return static
     */
    public function setConnection(ConnectionInterface $connection)
    {
        static::resolverFor(get_class($connection), $connection);

        return $this;
    }

    /**
     * @return ConnectionInterface
     */
    public function getConnection()
    {
        return static::getResolverByKey(\Wilkques\Database\Connections\ConnectionInterface::class);
    }

    /**
     * @param GrammarInterface $grammar
     * 
     * @return static
     */
    public function setGrammar(GrammarInterface $grammar = null)
    {
        static::resolverFor(get_class($grammar), $grammar);

        return $this;
    }

    /**
     * @return GrammarInterface
     */
    public function getGrammar()
    {
        return static::getResolverByKey(\Wilkques\Database\Queries\Grammar\GrammarInterface::class);
    }

    /**
     * @param ProcessInterface $process
     * 
     * @return static
     */
    public function setProcess(ProcessInterface $process = null)
    {
        static::resolverFor(get_class($process), $process);

        return $this;
    }

    /**
     * @return ProcessInterface
     */
    public function getProcess()
    {
        return static::getResolverByKey(\Wilkques\Database\Queries\Process\ProcessInterface::class);
    }

    /**
     * @param int|string $limit
     * 
     * @return static
     */
    public function setLimit($limit)
    {
        $this->getGrammar()->setLimit();

        $index = $this->nextArrayIndex($this->getLimit());

        return $this->setBindData("limit.{$index}", $limit);
    }

    /**
     * @param int|string
     */
    public function getLimit()
    {
        return $this->getBindData("limit");
    }

    /**
     * @param int|string $offset
     * 
     * @return static
     */
    public function setOffset($offset)
    {
        $this->getGrammar()->setOffset();

        $index = $this->nextArrayIndex($this->getOffset());

        return $this->setBindData("offset.{$index}", $offset);
    }

    /**
     * @param int|string
     */
    public function getOffset()
    {
        return $this->getBindData("offset");
    }

    /**
     * @param int|string $prePage
     * 
     * @return static
     */
    public function setPrePage($prePage = 10)
    {
        $this->paginate["prePage"] = $prePage;

        return $this;
    }

    /**
     * @return int|string
     */
    public function getPrePage()
    {
        return $this->paginate["prePage"];
    }

    /**
     * @param int $prePage
     * 
     * @return static
     */
    public function setCurrentPage(int $currentPage = 1)
    {
        $this->paginate["currentPage"] = $currentPage;

        return $this;
    }

    /**
     * @return int
     */
    public function getCurrentPage()
    {
        return $this->paginate["currentPage"];
    }

    /**
     * @param mixed $value
     * @param mixed|null $bindValue
     * 
     * @return array
     */
    public function raw($value, $bindValue = null)
    {
        return new \Wilkques\Database\Queries\Expression($value, $bindValue);
    }

    /**
     * @param string $column
     * 
     * @return static
     */
    public function setSelectRaw(string $column = "*")
    {
        return $this->select($this->raw($column));
    }

    /**
     * @param string $where
     * @param mixed|null $value
     * 
     * @return static
     */
    public function setWhereRaw(string $where, $value = null)
    {
        $this->getGrammar()->where($this->raw($where));

        $value && $this->setBindData("where", $value);

        return $this;
    }

    /**
     * @param array $bindData
     * 
     * @return Result
     */
    public function exec(array $bindData = [])
    {
        $query = $this->getQuery();

        $statement = $this->prepare($query);

        $statement->bindParams($bindData);

        if ($this->isLogging()) {
            $bindings = $statement->getParams();

            $this->setQueryLog(compact('query', 'bindings'));
        }

        return $statement->execute();
    }

    /**
     * @return static
     */
    public function get()
    {
        return $this->compilerSelect()
            ->exec($this->getForSelectBindData())
            ->fetchAllAssociative();
    }

    /**
     * @return static
     */
    public function first()
    {
        return $this->limit(1)
            ->compilerSelect()
            ->exec($this->getForSelectBindData())
            ->fetchAssociative();
    }

    /**
     * @param array $keys
     * 
     * @return array
     */
    public function getOnlyBindData(array $keys = null)
    {
        return \array_only($this->getBindData(), $keys);
    }

    /**
     * @param array $keys
     * 
     * @return array
     */
    public function getOnlyBindDataField(array $keys)
    {
        return \array_field($this->getOnlyBindData($keys), $keys);
    }

    /**
     * @param array $keys
     * 
     * @return array
     */
    public function getForSelectBindData(array $keys = array("where", "limit", "offset"))
    {
        return $this->getOnlyBindDataField($keys);
    }

    /**
     * @param array $bindData
     */
    public function withBindData($bindData)
    {
        $this->bindData = $bindData;

        return $this;
    }

    /**
     * @param string $key
     * @param mixed $value
     * 
     * @return static
     */
    public function setBindData(string $key, $value = null)
    {
        $bindData = $this->getBindData();

        array_set($bindData, $key, $value);

        $this->bindData = $bindData;

        return $this;
    }

    /**
     * @param string|null $key
     * @param mixed|null $default
     * 
     * @return string|array
     */
    public function getBindData(string $key = null, $default = null)
    {
        $bindData = $this->bindData;

        return array_get($bindData, $key, $default);
    }

    /**
     * @param array $data
     * 
     * @return int
     */
    public function nextArrayIndex($data)
    {
        $index = 0;

        $data && $index = array_key_last($data) + 1;

        return $index;
    }

    /**
     * @param array|string $key
     * @param string|mixed|null $condition
     * @param mixed|null $value
     * 
     * @return static
     */
    public function where($key, $condition = null, $value = null)
    {
        return $this->whereCondition($key, $condition, $value);
    }

    /**
     * @param array|string $key
     * @param string|mixed|null $condition
     * @param mixed|null $value
     * 
     * @return static
     */
    public function orWhere($key, $condition = null, $value = null)
    {
        return $this->whereCondition($key, $condition, $value, "OR", "orWhere");
    }

    /**
     * @param array|string $key
     * @param string|mixed $condition
     * @param mixed|null $value
     * @param string $andOr
     * @param string $method
     * 
     * @return static
     */
    protected function whereCondition($key, $condition, $value = null, string $andOr = "AND", string $method = "where")
    {
        if (is_array($key)) {
            array_map(function ($item) use ($method) {
                call_user_func_array(array($this, $method), $item);
            }, $key);

            return $this;
        }

        $whereBindData = $this->getBindData("where") ?: array();

        $index = $this->nextArrayIndex($whereBindData);

        if (!$value) {
            $value = $condition;
            $condition = "=";
        }

        $this->getGrammar()->where($key, $condition, $andOr);

        if ($key instanceof \Wilkques\Database\Queries\Expression) {
            $data = $key->getBindValue();

            array_push($whereBindData, ...$data);

            return $this->setBindData("where", $whereBindData);
        }

        $value && $this->setBindData("where.{$index}", $value);

        return $this;
    }

    /**
     * @param string $column
     * @param array  $data
     * 
     * @return static
     */
    public function whereIn($column, $data)
    {
        !is_string($column) && $this->argumentsThrowError(" First Arguments must be string");

        $query = implode(", ", array_fill(0, count($data), "?"));

        $whereBindData = $this->getBindData("where") ?: array();

        array_push($whereBindData, ...$data);

        return $this->setBindData("where", $whereBindData)->whereRaw("`{$column}` IN ({$query})");
    }

    /**
     * @return int
     */
    public function count()
    {
        return (int) $this->fromSub(function (self $query) {
            $query->compilerSelect(array("from", "where", "groupBy", "orderBy"));
        }, "total_count")
            ->selectRaw("COUNT(*) as count")
            ->compilerSelect(array("from"))
            ->exec($this->getForSelectBindData(array("from", "where")))
            ->fetchFirstColumn();
    }

    /**
     * @return static
     */
    public function getForPage()
    {
        $this->setLimit($this->getPrePage())->setOffset(((int) $this->getCurrentPage() - 1) * $this->getPrePage());

        $items = $this->get();

        $total = $this->count();

        return compact('total', 'items');
    }

    /**
     * @return array
     */
    public function getForUpdateBindData()
    {
        return $this->getOnlyBindDataField(array("update", "where"));
    }

    /**
     * @param array $data
     * 
     * @return static
     */
    public function update($data)
    {
        !is_array($data) && $this->argumentsThrowError(" first Arguments must be array");

        return $this->setBindData("update", array_values($data))
            ->setUpdate($data)
            ->compilerUpdate()
            ->exec($this->getForUpdateBindData())
            ->rowCount();
    }

    /**
     * @param string $column
     * @param int|string $value
     * @param array $data
     * 
     * @return static
     */
    public function increment($column, $value = 1, $data = array())
    {
        !is_numeric($value) && $this->argumentsThrowError(" second Arguments must be numeric");

        return $this->update($data + [
            $column => $this->raw("`{$column}` = `{$column}` + ?", $value)
        ]);
    }

    /**
     * @param string $column
     * @param int|string $value
     * @param array $data
     * 
     * @return static
     */
    public function decrement($column, $value = 1, $data = array())
    {
        !is_numeric($value) && $this->argumentsThrowError(" second Arguments must be numeric");

        return $this->update($data + [
            $column => $this->raw("`{$column}` = `{$column}` - ?", $value)
        ]);
    }

    /**
     * @return array
     */
    public function getForWhereBindData()
    {
        return $this->getOnlyBindDataField(array("where"));
    }

    /**
     * @return static
     */
    public function delete()
    {
        return $this->compilerDelete()
            ->exec($this->getForWhereBindData())
            ->rowCount();
    }

    /**
     * @param string $column
     * @param string $dateTimeFormat
     * 
     * @return static
     */
    public function softDelete(string $column = 'deleted_at', string $dateTimeFormat = "Y-m-d H:i:s")
    {
        !is_string($column) && $this->argumentsThrowError(" first Arguments must be string");

        $value = date($dateTimeFormat);

        return $this->update([
            $column => $value
        ]);
    }

    /**
     * @param string $column
     * 
     * @return static
     */
    public function reStore(string $column = 'deleted_at')
    {
        !is_string($column) && $this->argumentsThrowError(" first Arguments must be string");

        $value = null;

        return $this->update([
            $column => $value
        ]);
    }

    /**
     * @param array $data
     * 
     * @return static
     */
    public function insert($data)
    {
        !is_array($data) && $this->argumentsThrowError(" first Arguments must be array");

        return $this->setBindData("insert", $data)
            ->setInsert($data)
            ->compilerInsert()
            ->exec($this->getOnlyBindData(array("insert")))
            ->rowCount();
    }

    /**
     * @param string $from
     * @param mixed|null $value
     * 
     * @return static
     */
    public function setFromRaw($from, $value = null)
    {
        $this->getGrammar()->setFrom((string) $this->raw($from));

        $value && $this->setBindData("from", $value);

        return $this;
    }

    /**
     * Makes "from" fetch from a subquery.
     *
     * @param  \Closure|\Illuminate\Database\Query\Builder|string  $query
     * @param  string  $as
     * @return static
     *
     * @throws \InvalidArgumentException
     */
    public function fromSub($query, $as)
    {
        list($query, $bindData) = $this->createSub($query);

        return $this->setFromRaw('(' . $query . ') as ' . $as, $bindData);
    }

    /**
     * Creates a subquery and parse it.
     *
     * @param  \Closure|\Illuminate\Database\Query\Builder|string  $query
     * @return array
     */
    protected function createSub($query)
    {
        // If the given query is a Closure, we will execute it while passing in a new
        // query instance to the Closure. This will give the developer a chance to
        // format and work with the query before we cast it to a raw SQL string.
        if ($query instanceof \Closure) {
            $callback = $query;

            $callback($query = $this->forSubQuery());
        }

        return $this->parseSub($query);
    }

    /**
     * Create a new query instance for a sub-query.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    protected function forSubQuery()
    {
        return $this->newQuery();
    }

    /**
     * Parse the subquery into SQL and bindings.
     *
     * @param  mixed  $query
     * @return array
     *
     * @throws \InvalidArgumentException
     */
    protected function parseSub($query)
    {
        if ($query instanceof self) {
            $query = $this->prependDatabaseNameIfCrossDatabaseQuery($query);

            return array($query->getQuery(), $query->getBindData());
        } elseif (is_string($query)) {
            return array($query, array());
        } else {
            throw new \InvalidArgumentException(
                'A subquery must be a query builder instance, a Closure, or a string.'
            );
        }
    }

    /**
     * Prepend the database name if the given query is on another database.
     *
     * @param  mixed  $query
     * @return mixed
     */
    protected function prependDatabaseNameIfCrossDatabaseQuery($query)
    {
        if (
            $query->getConnection()->getDbname() !==
            $this->getConnection()->getDbname()
        ) {
            $databaseName = $query->getConnection()->getDbname();

            if (strpos($query->getFrom(), $databaseName) !== 0 && strpos($query->getFrom(), '.') === false) {
                $query->from($databaseName . '.' . $query->getFrom());
            }
        }

        return $query;
    }

    /**
     * Get a new instance of the query builder.
     *
     * @return \Wilkques\Database\Queries\Builder
     */
    public function newQuery()
    {
        return new static($this->getConnection(), $this->getGrammar(), $this->getProcess());
    }

    /**
     * @throws \UnexpectedValueException
     */
    protected function argumentsThrowError($message = "")
    {
        throw new \UnexpectedValueException(
            sprintf(
                "DB::%s arguments is error.%s",
                debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'],
                $message
            )
        );
    }

    /**
     * Register a connection resolver.
     *
     * @param  string  $abstract
     * @param  mixed  $class
     * @return void
     */
    public static function resolverFor($abstract, $class)
    {
        static::$resolvers[$abstract] = $class;
    }

    /**
     * Get the connection resolver for the given driver.
     *
     * @param  string  $abstract
     * @return mixed
     */
    public static function getResolver($abstract)
    {
        return static::$resolvers[$abstract] ?? null;
    }

    /**
     * @param string $method
     * 
     * @return string
     */
    protected function method($method)
    {
        $methods = array(
            "set"       => array(
                'table', 'username', 'password', 'dbname', 'host', 'bindData', 'select',
                'orderBy', 'groupBy', 'limit', 'offset', 'connection', 'grammar', 'currentPage',
                'prePage', "process", 'selectRaw', 'raw', 'whereRaw', "from", "fromRaw"
            ),
            "process"   => array(
                'InsertGetId'
            ),
            "get"       => array(
                "parseQueryLog", "lastParseQuery"
            )
        );

        foreach ($methods as $index => $item) {
            if (in_array($method, $item)) {
                $method = $index . ucfirst($method);

                break;
            }
        }

        return $method;
    }

    /**
     * @param string $method
     * @param array $arguments
     * 
     * @return static
     */
    public function __call($method, $arguments)
    {
        $method = $this->method($method);

        foreach (static::$resolvers as $abstract) {
            if (method_exists($abstract, $method)) {
                break;
            }
        }

        if ($abstract instanceof \Wilkques\Database\Queries\Process\ProcessInterface) {
            array_unshift($arguments, $this);
        }

        $abstract = call_user_func_array(array($abstract, $method), $arguments);

        is_object($abstract) && static::resolverFor(get_class($abstract), $abstract);

        if ($abstract instanceof \Wilkques\Database\Queries\Grammar\GrammarInterface) {
            $abstract = $this;
        }

        return $abstract;
    }
}
