<?php

namespace Typecho\Http\Client;

use Typecho\Exception as TypechoException;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * HTTP client exception class
 *
 * @package Http
 */
class Exception extends TypechoException
{
}
