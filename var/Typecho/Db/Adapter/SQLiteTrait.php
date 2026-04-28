<?php

namespace Typecho\Db\Adapter;

/**
 * SQLite Special Util
 */
trait SQLiteTrait
{
    use QueryTrait;

    private bool $isSQLite2 = false;

    /**
     * Clear data table
     *
     * @param string $table
     * @param mixed $handle Connection handle
     * @throws SQLException
     */
    public function truncate(string $table, $handle)
    {
        $this->query('DELETE FROM ' . $this->quoteColumn($table), $handle);
    }

    /**
     * Object quote filter
     *
     * @param string $string
     * @return string
     */
    public function quoteColumn(string $string): string
    {
        return '"' . $string . '"';
    }

    /**
     * Filter fields名
     *
     * @access private
     *
     * @param array $result
     *
     * @return array
     */
    private function filterColumnName(array $result): array
    {
        /** 如果结果为空,直接返回 */
        if (empty($result)) {
            return $result;
        }

        $tResult = [];

        /** 遍历数组 */
        foreach ($result as $key => $val) {
            /** 按点分隔 */
            if (false !== ($pos = strpos($key, '.'))) {
                $key = substr($key, $pos + 1);
            }

            $tResult[trim($key, '"')] = $val;
        }

        return $tResult;
    }

    /**
     * 处理sqlite2的distinct count
     *
     * @param string $sql
     *
     * @return string
     */
    private function filterCountQuery(string $sql): string
    {
        if (preg_match("/SELECT\s+COUNT\(DISTINCT\s+([^\)]+)\)\s+(AS\s+[^\s]+)?\s*FROM\s+(.+)/is", $sql, $matches)) {
            return 'SELECT COUNT(' . $matches[1] . ') ' . $matches[2] . ' FROM SELECT DISTINCT '
                . $matches[1] . ' FROM ' . $matches[3];
        }

        return $sql;
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
        $query = $this->buildQuery($sql);

        if ($this->isSQLite2) {
            $query = $this->filterCountQuery($query);
        }

        return $query;
    }

    /**
     * @return string
     */
    public function getDriver(): string
    {
        return 'sqlite';
    }
}
