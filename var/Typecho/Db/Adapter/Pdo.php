<?php

namespace Typecho\Db\Adapter;

use Typecho\Config;
use Typecho\Db;
use Typecho\Db\Adapter;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 数据库PDOMysql适配器
 *
 * @package Db
 */
abstract class Pdo implements Adapter
{
    /**
     * Database object
     *
     * @access protected
     * @var \PDO
     */
    protected \PDO $object;

    /**
     * 最后Mon次操作的数据表
     *
     * @access protected
     * @var string|null
     */
    protected ?string $lastTable;

    /**
     * Check whether the adapter is available
     *
     * @access public
     * @return boolean
     */
    public static function isAvailable(): bool
    {
        return class_exists('PDO');
    }

    /**
     * Database connection function
     *
     * @param Config $config Database configuration
     * @return \PDO
     * @throws ConnectionException
     */
    public function connect(Config $config): \PDO
    {
        try {
            $this->object = $this->init($config);
            $this->object->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            return $this->object;
        } catch (\PDOException $e) {
            /** Database exception */
            throw new ConnectionException($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Initialize database
     *
     * @param Config $config Database configuration
     * @abstract
     * @access public
     * @return \PDO
     */
    abstract public function init(Config $config): \PDO;

    /**
     * Get database version
     *
     * @param mixed $handle
     * @return string
     */
    public function getVersion($handle): string
    {
        return $handle->getAttribute(\PDO::ATTR_SERVER_VERSION);
    }

    /**
     * Execute database query
     *
     * @param string $query Database SQL query string
     * @param \PDO $handle Connection handle
     * @param integer $op Database read/write mode
     * @param string|null $action Database action
     * @param string|null $table Database table
     * @return \PDOStatement
     * @throws SQLException
     */
    public function query(
        string $query,
        $handle,
        int $op = Db::READ,
        ?string $action = null,
        ?string $table = null
    ): \PDOStatement {
        try {
            $this->lastTable = $table;
            $resource = $handle->prepare($query);
            $resource->execute();
        } catch (\PDOException $e) {
            /** Database exception */
            throw new SQLException($e->getMessage(), $e->getCode());
        }

        return $resource;
    }

    /**
     * Fetch all query results as an array, keyed by column name
     *
     * @param \PDOStatement $resource Query resource data
     * @return array
     */
    public function fetchAll($resource): array
    {
        return $resource->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Fetch one row from the query result as an array, keyed by column name
     *
     * @param \PDOStatement $resource Query result resource
     * @return array|null
     */
    public function fetch($resource): ?array
    {
        return $resource->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Fetch one row from the query result as an object, with column names as properties
     *
     * @param \PDOStatement $resource Query resource data
     * @return \stdClass|null
     */
    public function fetchObject($resource): ?\stdClass
    {
        return $resource->fetchObject() ?: null;
    }

    /**
     * Quote escaping function
     *
     * @param mixed $string String to escape
     * @return string
     */
    public function quoteValue($string): string
    {
        return $this->object->quote($string);
    }

    /**
     * Get the number of rows affected by the last query
     *
     * @param \PDOStatement $resource Query resource data
     * @param \PDO $handle Connection handle
     * @return integer
     */
    public function affectedRows($resource, $handle): int
    {
        return $resource->rowCount();
    }

    /**
     * Get the primary key value returned by the last insert
     *
     * @param \PDOStatement $resource Query resource data
     * @param \PDO $handle Connection handle
     * @return integer
     */
    public function lastInsertId($resource, $handle): int
    {
        return $handle->lastInsertId();
    }
}
