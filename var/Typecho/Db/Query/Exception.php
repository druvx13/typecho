<?php

namespace Typecho\Db\Query;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

use Typecho\Db\Exception as DbException;

/**
 * Database query exception class
 *
 * @package Db
 */
class Exception extends DbException
{
}
