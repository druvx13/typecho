<?php

namespace Typecho\Db\Adapter\Pdo;

use Typecho\Config;
use Typecho\Db;
use Typecho\Db\Adapter\SQLException;
use Typecho\Db\Adapter\Pdo;
use Typecho\Db\Adapter\PgsqlTrait;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 数据库Pdo_Pgsql适配器
 *
 * @package Db
 */
class Pgsql extends Pdo
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
        return parent::isAvailable() && in_array('pgsql', \PDO::getAvailableDrivers());
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
        $this->prepareQuery($query, $handle, $action, $table);
        return parent::query($query, $handle, $op, $action, $table);
    }

    /**
     * Initialize database
     *
     * @param Config $config Database configuration
     * @return \PDO
     */
    public function init(Config $config): \PDO
    {
        $dsn = "pgsql:dbname={$config->database};host={$config->host};port={$config->port}";

        if ($config->sslVerify) {
            $dsn .= ';sslmode=require';
        }

        $pdo = new \PDO(
            $dsn,
            $config->user,
            $config->password
        );

        if ($config->charset) {
            $pdo->exec("SET NAMES '{$config->charset}'");
        }

        return $pdo;
    }
}
