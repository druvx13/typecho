<?php
/**
 * Typecho Blog Platform
 *
 * @copyright  Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license    GNU General Public License 2.0
 * @version    $Id: index.php 1153 2009-07-02 10:53:22Z magike.net $
 */

/** Load configuration support */
if (!defined('__TYPECHO_ROOT_DIR__') && !@include_once 'config.inc.php') {
    file_exists('./install.php') ? header('Location: install.php') : print('Missing Config File');
    exit;
}

/** Initialize widget */
\Widget\Init::alloc();

/** Register an initialization plugin */
\Typecho\Plugin::factory('index.php')->call('begin');

/** Begin route dispatch */
\Typecho\Router::dispatch();

/** Register a shutdown plugin */
\Typecho\Plugin::factory('index.php')->call('end');
