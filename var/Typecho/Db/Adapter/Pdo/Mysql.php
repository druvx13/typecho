<?php

namespace Typecho\Db\Adapter\Pdo;

use Typecho\Config;
use Typecho\Db\Adapter\MysqlTrait;
use Typecho\Db\Adapter\Pdo;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 数据库Pdo_Mysql适配器
 *
 * @package Db
 */
class Mysql extends Pdo
{
    use MysqlTrait;

    /**
     * Check whether the adapter is available
     *
     * @access public
     * @return boolean
     */
    public static function isAvailable(): bool
    {
        return parent::isAvailable() && in_array('mysql', \PDO::getAvailableDrivers());
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
     * Initialize database
     *
     * @param Config $config Database configuration
     * @access public
     * @return \PDO
     */
    public function init(Config $config): \PDO
    {
        $options = [];
        if (!empty($config->sslCa)) {
            $options[\PDO::MYSQL_ATTR_SSL_CA] = $config->sslCa;

            if (isset($config->sslVerify)) {
                // FIXME: https://github.com/php/php-src/issues/8577
                $options[\PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = $config->sslVerify;
            }
        }

        $dsn = !empty($config->dsn)
            ? $config->dsn
            : (strpos($config->host, '/') !== false
                ? "mysql:dbname={$config->database};unix_socket={$config->host}"
                : "mysql:dbname={$config->database};host={$config->host};port={$config->port}");

        $pdo = new \PDO(
            $dsn,
            $config->user,
            $config->password,
            $options
        );

        if (class_exists('\Pdo\Mysql')) {
            // 新版本写法
            $pdo->setAttribute(\Pdo\Mysql::ATTR_USE_BUFFERED_QUERY, true);
        } else {
            // 兼容旧版本
            $pdo->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
        }

        if ($config->charset) {
            $pdo->exec("SET NAMES '{$config->charset}'");
        }

        return $pdo;
    }

    /**
     * Quote escaping function
     *
     * @param mixed $string String to escape
     * @return string
     */
    public function quoteValue($string): string
    {
        return '\'' . str_replace(['\'', '\\'], ['\'\'', '\\\\'], $string) . '\'';
    }
}
