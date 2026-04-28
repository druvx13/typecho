<?php

namespace Widget\Users;

use Typecho\Db\Exception;
use Widget\Base\Users;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * Related content widget (by tag association)
 *
 * @author qining
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Author extends Users
{
    /**
     * Execute function, initialize data
     *
     * @throws Exception
     */
    public function execute()
    {
        if (isset($this->parameter->uid)) {
            $this->db->fetchRow($this->select()
                ->where('uid = ?', $this->parameter->uid), [$this, 'push']);
        }
    }
}
