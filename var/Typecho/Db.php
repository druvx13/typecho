<?php

namespace Typecho;

use Typecho\Db\Adapter;
use Typecho\Db\Query;
use Typecho\Db\Exception as DbException;

/**
 * Class containing data retrieval helper methods.
 * Requires __TYPECHO_DB_HOST__, __TYPECHO_DB_PORT__, __TYPECHO_DB_NAME__,
 * __TYPECHO_DB_USER__, __TYPECHO_DB_PASS__, __TYPECHO_DB_CHAR__
 *
 * @package Db
 */
class Db
{
    /** Read from database */
    public const READ = 1;

    /** Write to database */
    public const WRITE = 2;

    /** Ascending order */
    public const SORT_ASC = 'ASC';

    /** Descending order */
    public const SORT_DESC = 'DESC';

    /** Inner join */
    public const INNER_JOIN = 'INNER';

    /** Outer join */
    public const OUTER_JOIN = 'OUTER';

    /** Left join */
    public const LEFT_JOIN = 'LEFT';

    /** Right join */
    public const RIGHT_JOIN = 'RIGHT';

    /** Database read operation */
    public const SELECT = 'SELECT';

    /** Database write operation */
    public const UPDATE = 'UPDATE';

    /** Database insert operation */
    public const INSERT = 'INSERT';

    /** Database delete operation */
    public const DELETE = 'DELETE';

    /**
     * Database adapter
     * @var Adapter
     */
    private Adapter $adapter;

    /**
     * Default configuration
     *
     * @var array
     */
    private array $config;

    /**
     * Already connected
     *
     * @access private
     * @var array
     */
    private array $connectedPool;

    /**
     * Prefix
     *
     * @access private
     * @var string
     */
    private string $prefix;

    /**
     * Adapter name
     *
     * @access private
     * @var string
     */
    private string $adapterName;

    /**
     * Instantiated database object
     * @var Db
     */
    private static Db $instance;

    /**
     * Database class constructor
     *
     * @param mixed $adapterName Adapter name
     * @param string $prefix Prefix
     *
     * @throws DbException
     */
    public function __construct($adapterName, string $prefix = 'typecho_')
    {
        /** Get adapter name */
        $adapterName = $adapterName == 'Mysql' ? 'Mysqli' : $adapterName;
        $this->adapterName = $adapterName;

        /** Database adapter */
        $adapterName = '\Typecho\Db\Adapter\\' . str_replace('_', '\\', $adapterName);

        if (!call_user_func([$adapterName, 'isAvailable'])) {
            throw new DbException("Adapter {$adapterName} is not available");
        }

        $this->prefix = $prefix;

        /** Initialize internal variables */
        $this->connectedPool = [];

        $this->config = [
            self::READ => [],
            self::WRITE => []
        ];

        // Instantiate adapter object
        $this->adapter = new $adapterName();
    }

    /**
     * @return Adapter
     */
    public function getAdapter(): Adapter
    {
        return $this->adapter;
    }

    /**
     * Get adapter name
     *
     * @access public
     * @return string
     */
    public function getAdapterName(): string
    {
        return $this->adapterName;
    }

    /**
     * Get table prefix
     *
     * @access public
     * @return string
     */
    public function getPrefix(): string
    {
        return $this->prefix;
    }

    /**
     * @param Config $config
     * @param int $op
     */
    public function addConfig(Config $config, int $op)
    {
        if ($op & self::READ) {
            $this->config[self::READ][] = $config;
        }

        if ($op & self::WRITE) {
            $this->config[self::WRITE][] = $config;
        }
    }

    /**
     * getConfig
     *
     * @param int $op
     *
     * @return Config
     * @throws DbException
     */
    public function getConfig(int $op): Config
    {
        if (empty($this->config[$op])) {
            /** DbException */
            throw new DbException('Missing Database Connection');
        }

        $key = array_rand($this->config[$op]);
        return $this->config[$op][$key];
    }

    /**
     * Reset connection pool
     *
     * @return void
     */
    public function flushPool()
    {
        $this->connectedPool = [];
    }

    /**
     * Select database
     *
     * @param int $op
     *
     * @return mixed
     * @throws DbException
     */
    public function selectDb(int $op)
    {
        if (!isset($this->connectedPool[$op])) {
            $selectConnectionConfig = $this->getConfig($op);
            $selectConnectionHandle = $this->adapter->connect($selectConnectionConfig);
            $this->connectedPool[$op] = $selectConnectionHandle;
        }

        return $this->connectedPool[$op];
    }

    /**
     * Get SQL lexical builder instance
     *
     * @return Query
     */
    public function sql(): Query
    {
        return new Query($this->adapter, $this->prefix);
    }

    /**
     * Provide support for multiple databases
     *
     * @access public
     * @param array $config Database instance
     * @param integer $op Database operation
     * @return void
     */
    public function addServer(array $config, int $op)
    {
        $this->addConfig(Config::factory($config), $op);
        $this->flushPool();
    }

    /**
     * Get version
     *
     * @param int $op
     *
     * @return string
     * @throws DbException
     */
    public function getVersion(int $op = self::READ): string
    {
        return $this->adapter->getVersion($this->selectDb($op));
    }

    /**
     * Set default database object
     *
     * @access public
     * @param Db $db Database object
     * @return void
     */
    public static function set(Db $db)
    {
        self::$instance = $db;
    }

    /**
     * Get database instance object
     * Use static variable to store database instance; ensures connection is made only once
     *
     * @return Db
     * @throws DbException
     */
    public static function get(): Db
    {
        if (empty(self::$instance)) {
            /** DbException */
            throw new DbException('Missing Database Object');
        }

        return self::$instance;
    }

    /**
     * Select query fields
     *
     * @param ...$ags
     *
     * @return Query
     * @throws DbException
     */
    public function select(...$ags): Query
    {
        $this->selectDb(self::READ);

        $args = func_get_args();
        return call_user_func_array([$this->sql(), 'select'], $args ?: ['*']);
    }

    /**
     * Update record operation (UPDATE)
     *
     * @param string $table Table to update
     *
     * @return Query
     * @throws DbException
     */
    public function update(string $table): Query
    {
        $this->selectDb(self::WRITE);

        return $this->sql()->update($table);
    }

    /**
     * Delete record operation (DELETE)
     *
     * @param string $table Table to delete from
     *
     * @return Query
     * @throws DbException
     */
    public function delete(string $table): Query
    {
        $this->selectDb(self::WRITE);

        return $this->sql()->delete($table);
    }

    /**
     * Insert record operation (INSERT)
     *
     * @param string $table Table to insert into
     *
     * @return Query
     * @throws DbException
     */
    public function insert(string $table): Query
    {
        $this->selectDb(self::WRITE);

        return $this->sql()->insert($table);
    }

    /**
     * @param $table
     * @throws DbException
     */
    public function truncate($table)
    {
        $table = preg_replace("/^table\./", $this->prefix, $table);
        $this->adapter->truncate($table, $this->selectDb(self::WRITE));
    }

    /**
     * Execute query statement
     *
     * @param mixed $query Query string or query object
     * @param int $op Database read/write mode
     * @param string $action Operation action
     *
     * @return mixed
     * @throws DbException
     */
    public function query($query, int $op = self::READ, string $action = self::SELECT)
    {
        $table = null;

        /** Execute query in adapter */
        if ($query instanceof Query) {
            $action = $query->getAttribute('action');
            $table = $query->getAttribute('table');
            $op = (self::UPDATE == $action || self::DELETE == $action
                || self::INSERT == $action) ? self::WRITE : self::READ;
        } elseif (!is_string($query)) {
            /** If query is neither object nor string, treat as query resource handle and return directly */
            return $query;
        }

        /** Select connection pool */
        $handle = $this->selectDb($op);

        /** If query object, convert to query string */
        $sql = $query instanceof Query ? $query->prepare($query) : $query;

        /** Submit query */
        $resource = $this->adapter->query($sql, $handle, $op, $action, $table);

        if ($action) {
            // Return corresponding resource based on query action
            switch ($action) {
                case self::UPDATE:
                case self::DELETE:
                    return $this->adapter->affectedRows($resource, $handle);
                case self::INSERT:
                    return $this->adapter->lastInsertId($resource, $handle);
                case self::SELECT:
                default:
                    return $resource;
            }
        } else {
            // If query string is given directly, return resource
            return $resource;
        }
    }

    /**
     * Fetch all rows at once
     *
     * @param mixed $query Query object
     * @param callable|null $filter Row filter function; each row is passed as the first argument
     *
     * @return array
     * @throws DbException
     */
    public function fetchAll($query, ?callable $filter = null): array
    {
        //Execute query
        $resource = $this->query($query);
        $result = $this->adapter->fetchAll($resource);

        return $filter ? array_map($filter, $result) : $result;
    }

    /**
     * Fetch one row at a time
     *
     * @param mixed $query Query object
     * @param callable|null $filter Row filter function; each row is passed as the first argument
     * @return array|null
     * @throws DbException
     */
    public function fetchRow($query, ?callable $filter = null): ?array
    {
        $resource = $this->query($query);

        return ($rows = $this->adapter->fetch($resource)) ?
            ($filter ? call_user_func($filter, $rows) : $rows) :
            null;
    }

    /**
     * Fetch one object at a time
     *
     * @param mixed $query Query object
     * @param array|null $filter Row filter function; each row is passed as the first argument
     * @return \stdClass|null
     * @throws DbException
     */
    public function fetchObject($query, ?array $filter = null): ?\stdClass
    {
        $resource = $this->query($query);

        return ($rows = $this->adapter->fetchObject($resource)) ?
            ($filter ? call_user_func($filter, $rows) : $rows) :
            null;
    }
}
