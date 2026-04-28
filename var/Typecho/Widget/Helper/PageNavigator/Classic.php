<?php

namespace Typecho\Widget\Helper\PageNavigator;

use Typecho\Widget\Helper\PageNavigator;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * Classic pagination style
 *
 * @author qining
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Classic extends PageNavigator
{
    /**
     * 输出经典样式的Pagination
     *
     * @access public
     * @param string $prevWord Previous page text
     * @param string $nextWord Next page text
     * @return void
     */
    public function render(string $prevWord = 'PREV', string $nextWord = 'NEXT')
    {
        $this->prev($prevWord);
        $this->next($nextWord);
    }

    /**
     * 输出上Mon页
     *
     * @access public
     * @param string $prevWord Previous page text
     * @return void
     */
    public function prev(string $prevWord = 'PREV')
    {
        // Output previous page
        if ($this->total > 0 && $this->currentPage > 1) {
            echo '<a class="prev" href="'
                . str_replace($this->pageHolder, $this->currentPage - 1, $this->pageTemplate)
                . $this->anchor . '">'
                . $prevWord . '</a>';
        }
    }

    /**
     * 输出下Mon页
     *
     * @access public
     * @param string $nextWord Next page text
     * @return void
     */
    public function next(string $nextWord = 'NEXT')
    {
        // Output next page
        if ($this->total > 0 && $this->currentPage < $this->totalPage) {
            echo '<a class="next" title="" href="'
                . str_replace($this->pageHolder, $this->currentPage + 1, $this->pageTemplate)
                . $this->anchor . '">'
                . $nextWord . '</a>';
        }
    }
}
