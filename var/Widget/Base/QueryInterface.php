<?php

namespace Widget\Base;

use Typecho\Db\Query;

/**
 * Base Query Interface
 */
interface QueryInterface
{
    /**
     * Query method
     *
     * @param mixed $fields 字段
     * @return Query
     */
    public function select(...$fields): Query;

    /**
     * Get total record count
     *
     * @access public
     * @param Query $condition Query object
     * @return integer
     */
    public function size(Query $condition): int;

    /**
     * Add record method
     *
     * @access public
     * @param array $rows Field values
     * @return integer
     */
    public function insert(array $rows): int;

    /**
     * Update record method
     *
     * @access public
     * @param array $rows Field values
     * @param Query $condition Query object
     * @return integer
     */
    public function update(array $rows, Query $condition): int;

    /**
     * Delete record method
     *
     * @access public
     * @param Query $condition Query object
     * @return integer
     */
    public function delete(Query $condition): int;
}
