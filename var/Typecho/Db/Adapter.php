<?php

namespace Typecho\Db;

use Typecho\Config;
use Typecho\Db;

/**
 * Typecho database adapter
 * 定义通用的数据库适配接口
 *
 * @package Db
 */
interface Adapter
{
    /**
     * Check whether the adapter is available
     *
     * @access public
     * @return boolean
     */
    public static function isAvailable(): bool;

    /**
     * Database connection function
     *
     * @param Config $config Database configuration
     * @return mixed
     */
    public function connect(Config $config);

    /**
     * Get database version
     *
     * @param mixed $handle
     * @return string
     */
    public function getVersion($handle): string;

    /**
     * 获取数据库类型
     *
     * @return string
     */
    public function getDriver(): string;

    /**
     * Clear data table
     *
     * @param string $table 数据表名
     * @param mixed $handle Connection handle
     */
    public function truncate(string $table, $handle);

    /**
     * Execute database query
     *
     * @param string $query Database SQL query string
     * @param mixed $handle Connection handle
     * @param integer $op Database read/write mode
     * @param string|null $action Database action
     * @param string|null $table Database table
     * @return resource
     */
    public function query(string $query, $handle, int $op = Db::READ, ?string $action = null, ?string $table = null);

    /**
     * Fetch one row from the query result as an array, keyed by column name
     *
     * @param resource $resource Query resource data
     * @return array|null
     */
    public function fetch($resource): ?array;

    /**
     * Fetch all query results as an array, keyed by column name
     *
     * @param resource $resource Query resource data
     * @return array
     */
    public function fetchAll($resource): array;

    /**
     * Fetch one row from the query result as an object, with column names as properties
     *
     * @param resource $resource Query resource data
     * @return \stdClass|null
     */
    public function fetchObject($resource): ?\stdClass;

    /**
     * Quote escaping function
     *
     * @param mixed $string String to escape
     * @return string
     */
    public function quoteValue($string): string;

    /**
     * Object quote filter
     *
     * @access public
     * @param string $string
     * @return string
     */
    public function quoteColumn(string $string): string;

    /**
     * Build query statement
     *
     * @access public
     * @param array $sql Query lexical array
     * @return string
     */
    public function parseSelect(array $sql): string;

    /**
     * Get the number of rows affected by the last query
     *
     * @param resource $resource Query resource data
     * @param mixed $handle Connection handle
     * @return integer
     */
    public function affectedRows($resource, $handle): int;

    /**
     * Get the primary key value returned by the last insert
     *
     * @param resource $resource Query resource data
     * @param mixed $handle Connection handle
     * @return integer
     */
    public function lastInsertId($resource, $handle): int;
}
