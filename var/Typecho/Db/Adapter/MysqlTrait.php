<?php

namespace Typecho\Db\Adapter;

trait MysqlTrait
{
    use QueryTrait;

    /**
     * Clear data table
     *
     * @param string $table
     * @param mixed $handle Connection handle
     * @throws SQLException
     */
    public function truncate(string $table, $handle)
    {
        $this->query('TRUNCATE TABLE ' . $this->quoteColumn($table), $handle);
    }

    /**
     * Build query statement
     *
     * @access public
     * @param array $sql Query lexical array
     * @return string
     */
    public function parseSelect(array $sql): string
    {
        return $this->buildQuery($sql);
    }

    /**
     * @return string
     */
    public function getDriver(): string
    {
        return 'mysql';
    }
}
