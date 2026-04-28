<?php

namespace Typecho\Db\Adapter;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

use Typecho\Db\Exception as DbException;

/**
 * Database connection exception class
 *
 * @package Db
 */
class ConnectionException extends DbException
{
}
