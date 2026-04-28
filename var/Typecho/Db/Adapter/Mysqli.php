<?php

namespace Typecho\Db\Adapter;

use Typecho\Config;
use Typecho\Db;
use Typecho\Db\Adapter;
use mysqli_sql_exception;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 数据库Mysqli适配器
 *
 * @package Db
 */
class Mysqli implements Adapter
{
    use MysqlTrait;

    /**
     * 数据库连接String标示
     *
     * @access private
     * @var \mysqli
     */
    private \mysqli $dbLink;

    /**
     * Check whether the adapter is available
     *
     * @access public
     * @return boolean
     */
    public static function isAvailable(): bool
    {
        return extension_loaded('mysqli');
    }

    /**
     * Database connection function
     *
     * @param Config $config Database configuration
     * @return \mysqli
     * @throws ConnectionException
     */
    public function connect(Config $config): \mysqli
    {
        $mysqli = mysqli_init();
        if ($mysqli) {
            try {
                if (!empty($config->sslCa)) {
                    $mysqli->ssl_set(null, null, $config->sslCa, null, null);

                    if (isset($config->sslVerify)) {
                        $mysqli->options(MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, $config->sslVerify);
                    }
                }

                $host = $config->host;
                $port = empty($config->port) ? null : $config->port;
                $socket = null;
                if (strpos($host, '/') !== false) {
                    $socket = $host;
                    $host = 'localhost';
                    $port = null;
                }

                $mysqli->real_connect(
                    $host,
                    $config->user,
                    $config->password,
                    $config->database,
                    $port,
                    $socket
                );

                $this->dbLink = $mysqli;

                if ($config->charset) {
                    $this->dbLink->query("SET NAMES '{$config->charset}'");
                }
            } catch (mysqli_sql_exception $e) {
                throw new ConnectionException($e->getMessage(), $e->getCode());
            }

            return $this->dbLink;
        }

        /** Database exception */
        throw new ConnectionException("Couldn't connect to database.", mysqli_connect_errno());
    }

    /**
     * Get database version
     *
     * @param mixed $handle
     * @return string
     */
    public function getVersion($handle): string
    {
        return $this->dbLink->server_version;
    }

    /**
     * Execute database query
     *
     * @param string $query Database SQL query string
     * @param mixed $handle Connection handle
     * @param integer $op Database read/write mode
     * @param string|null $action Database action
     * @param string|null $table Database table
     * @throws SQLException
     */
    public function query(
        string $query,
        $handle,
        int $op = Db::READ,
        ?string $action = null,
        ?string $table = null
    ) {
        try {
            if ($resource = $this->dbLink->query($query)) {
                return $resource;
            }
        } catch (mysqli_sql_exception $e) {
            /** Database exception */
            throw new SQLException($e->getMessage(), $e->getCode());
        }

        /** Database exception */
        throw new SQLException($this->dbLink->error, $this->dbLink->errno);
    }

    /**
     * Object quote filter
     *
     * @access public
     * @param string $string
     * @return string
     */
    public function quoteColumn(string $string): string
    {
        return '`' . $string . '`';
    }

    /**
     * Fetch one row from the query result as an array, keyed by column name
     *
     * @param \mysqli_result $resource Query result resource
     * @return array|null
     */
    public function fetch($resource): ?array
    {
        return $resource->fetch_assoc();
    }

    /**
     * Fetch all query results as an array, keyed by column name
     *
     * @param \mysqli_result $resource Query result resource
     * @return array
     */
    public function fetchAll($resource): array
    {
        return $resource->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Fetch one row from the query result as an object, with column names as properties
     *
     * @param \mysqli_result $resource 查询的资源数据
     * @return \stdClass|null
     */
    public function fetchObject($resource): ?\stdClass
    {
        return $resource->fetch_object();
    }

    /**
     * Quote escaping function
     *
     * @param mixed $string String to escape
     * @return string
     */
    public function quoteValue($string): string
    {
        return "'" . $this->dbLink->real_escape_string($string) . "'";
    }

    /**
     * Get the number of rows affected by the last query
     *
     * @param mixed $resource Query resource data
     * @param \mysqli $handle Connection handle
     * @return integer
     */
    public function affectedRows($resource, $handle): int
    {
        return $handle->affected_rows;
    }

    /**
     * Get the primary key value returned by the last insert
     *
     * @param mixed $resource Query resource data
     * @param \mysqli $handle Connection handle
     * @return integer
     */
    public function lastInsertId($resource, $handle): int
    {
        return $handle->insert_id;
    }
}
