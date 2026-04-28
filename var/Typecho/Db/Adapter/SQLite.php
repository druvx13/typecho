<?php

namespace Typecho\Db\Adapter;

use Typecho\Config;
use Typecho\Db;
use Typecho\Db\Adapter;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 数据库SQLite adapter
 *
 * @package Db
 */
class SQLite implements Adapter
{
    use SQLiteTrait;

    /**
     * Check whether the adapter is available
     *
     * @access public
     * @return boolean
     */
    public static function isAvailable(): bool
    {
        return extension_loaded('sqlite3');
    }

    /**
     * Database connection function
     *
     * @param Config $config Database configuration
     * @return \SQLite3
     * @throws ConnectionException
     */
    public function connect(Config $config): \SQLite3
    {
        try {
            $dbHandle = new \SQLite3($config->file);
            $this->isSQLite2 = version_compare(\SQLite3::version()['versionString'], '3.0.0', '<');
        } catch (\Exception $e) {
            throw new ConnectionException($e->getMessage(), $e->getCode());
        }

        return $dbHandle;
    }

    /**
     * Get database version
     *
     * @param mixed $handle
     * @return string
     */
    public function getVersion($handle): string
    {
        return \SQLite3::version()['versionString'];
    }

    /**
     * Execute database query
     *
     * @param string $query Database SQL query string
     * @param \SQLite3 $handle Connection handle
     * @param integer $op Database read/write mode
     * @param string|null $action Database action
     * @param string|null $table Database table
     * @return \SQLite3Result
     * @throws SQLException
     */
    public function query(
        string $query,
        $handle,
        int $op = Db::READ,
        ?string $action = null,
        ?string $table = null
    ): \SQLite3Result {
        if ($stm = $handle->prepare($query)) {
            if ($resource = $stm->execute()) {
                return $resource;
            }
        }

        /** Database exception */
        throw new SQLException($handle->lastErrorMsg(), $handle->lastErrorCode());
    }

    /**
     * Fetch one row from the query result as an object, with column names as properties
     *
     * @param \SQLite3Result $resource Query resource data
     * @return \stdClass|null
     */
    public function fetchObject($resource): ?\stdClass
    {
        $result = $this->fetch($resource);
        return $result ? (object) $result : null;
    }

    /**
     * Fetch one row from the query result as an array, keyed by column name
     *
     * @param \SQLite3Result $resource 查询返回资源标识
     * @return array|null
     */
    public function fetch($resource): ?array
    {
        $result = $resource->fetchArray(SQLITE3_ASSOC);
        return $result ? $this->filterColumnName($result) : null;
    }

    /**
     * Fetch all query results as an array, keyed by column name
     *
     * @param \SQLite3Result $resource Query resource data
     * @return array
     */
    public function fetchAll($resource): array
    {
        $result = [];

        while ($row = $this->fetch($resource)) {
            $result[] = $row;
        }

        return $result;
    }

    /**
     * Quote escaping function
     *
     * @param mixed $string String to escape
     * @return string
     */
    public function quoteValue($string): string
    {
        return '\'' . str_replace('\'', '\'\'', $string) . '\'';
    }

    /**
     * Get the number of rows affected by the last query
     *
     * @param \SQLite3Result $resource Query resource data
     * @param \SQLite3 $handle Connection handle
     * @return integer
     */
    public function affectedRows($resource, $handle): int
    {
        return $handle->changes();
    }

    /**
     * Get the primary key value returned by the last insert
     *
     * @param \SQLite3Result $resource Query resource data
     * @param \SQLite3 $handle Connection handle
     * @return integer
     */
    public function lastInsertId($resource, $handle): int
    {
        return $handle->lastInsertRowID();
    }
}
