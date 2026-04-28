<?php

namespace Typecho\Db\Adapter;

use Typecho\Config;
use Typecho\Db;
use Typecho\Db\Adapter;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 数据库Pgsql适配器
 *
 * @package Db
 */
class Pgsql implements Adapter
{
    use PgsqlTrait;

    /**
     * Check whether the adapter is available
     *
     * @access public
     * @return boolean
     */
    public static function isAvailable(): bool
    {
        return extension_loaded('pgsql');
    }

    /**
     * Database connection function
     *
     * @param Config $config Database configuration
     * @return resource
     * @throws ConnectionException
     */
    public function connect(Config $config)
    {
        $dsn = "host={$config->host} port={$config->port}"
            . " dbname={$config->database} user={$config->user} password={$config->password}";

        if ($config->sslVerify) {
            $dsn .= ' sslmode=require';
        }

        if ($config->charset) {
            $dsn .= " options='--client_encoding={$config->charset}'";
        }

        if ($dbLink = @pg_connect($dsn)) {
            return $dbLink;
        }

        /** Database exception */
        throw new ConnectionException("Couldn't connect to database.");
    }

    /**
     * Get database version
     *
     * @param mixed $handle
     * @return string
     */
    public function getVersion($handle): string
    {
        $version = pg_version($handle);
        return $version['server'];
    }

    /**
     * Execute database query
     *
     * @param string $query Database SQL query string
     * @param resource $handle Connection handle
     * @param integer $op Database read/write mode
     * @param string|null $action Database action
     * @param string|null $table Database table
     * @return resource
     * @throws SQLException
     */
    public function query(string $query, $handle, int $op = Db::READ, ?string $action = null, ?string $table = null)
    {
        $this->prepareQuery($query, $handle, $action, $table);
        if ($resource = pg_query($handle, $query)) {
            return $resource;
        }

        /** Database exception */
        throw new SQLException(
            @pg_last_error($handle),
            pg_result_error_field(pg_get_result($handle), PGSQL_DIAG_SQLSTATE)
        );
    }

    /**
     * Fetch one row from the query result as an array, keyed by column name
     *
     * @param resource $resource 查询返回资源标识
     * @return array|null
     */
    public function fetch($resource): ?array
    {
        return pg_fetch_assoc($resource) ?: null;
    }

    /**
     * Fetch one row from the query result as an object, with column names as properties
     *
     * @param resource $resource Query resource data
     * @return \stdClass|null
     */
    public function fetchObject($resource): ?\stdClass
    {
        return pg_fetch_object($resource) ?: null;
    }

    /**
     * @param resource $resource
     * @return array
     */
    public function fetchAll($resource): array
    {
        return pg_fetch_all($resource, PGSQL_ASSOC) ?: [];
    }

    /**
     * Get the number of rows affected by the last query
     *
     * @param resource $resource Query resource data
     * @param resource $handle Connection handle
     * @return integer
     */
    public function affectedRows($resource, $handle): int
    {
        return pg_affected_rows($resource);
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
}
