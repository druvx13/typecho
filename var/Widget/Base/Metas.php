<?php

namespace Widget\Base;

use Typecho\Common;
use Typecho\Db\Exception;
use Typecho\Db\Query;
use Typecho\Router;
use Typecho\Router\ParamsDelegateInterface;
use Widget\Base;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 描述性数据组件
 *
 * @property int $mid
 * @property string $name
 * @property string $title
 * @property string $slug
 * @property string $type
 * @property string $description
 * @property int $count
 * @property int $order
 * @property int $parent
 * @property-read string $theId
 * @property-read string $url
 * @property-read string $permalink
 * @property-read string[] $directory
 * @property-read string $feedUrl
 * @property-read string $feedRssUrl
 * @property-read string $feedAtomUrl
 */
class Metas extends Base implements QueryInterface, RowFilterInterface, PrimaryKeyInterface, ParamsDelegateInterface
{
    /**
     * @return string Get primary key
     */
    public function getPrimaryKey(): string
    {
        return 'mid';
    }

    /**
     * @param string $key
     * @return string
     */
    public function getRouterParam(string $key): string
    {
        switch ($key) {
            case 'mid':
                return (string)$this->mid;
            case 'slug':
                return urlencode($this->slug);
            case 'directory':
                return implode('/', array_map('urlencode', $this->directory));
            default:
                return '{' . $key . '}';
        }
    }

    /**
     * Get total record count
     *
     * @param Query $condition Count condition
     * @return integer
     * @throws Exception
     */
    public function size(Query $condition): int
    {
        return $this->db->fetchObject($condition->select(['COUNT(mid)' => 'num'])->from('table.metas'))->num;
    }

    /**
     * Push each row value onto the stack
     *
     * @param array $value Row values
     * @return array
     */
    public function push(array $value): array
    {
        $value = $this->filter($value);
        return parent::push($value);
    }

    /**
     * General filter
     *
     * @param array $row Row data to filter
     * @return array
     */
    public function filter(array $row): array
    {
        return Metas::pluginHandle()->filter('filter', $row, $this);
    }

    /**
     * Update record
     *
     * @param array $rows Record update values
     * @param Query $condition Update condition
     * @return integer
     * @throws Exception
     */
    public function update(array $rows, Query $condition): int
    {
        return $this->db->query($condition->update('table.metas')->rows($rows));
    }

    /**
     * Get raw query object
     *
     * @param mixed $fields
     * @return Query
     * @throws Exception
     */
    public function select(...$fields): Query
    {
        return $this->db->select(...$fields)->from('table.metas');
    }

    /**
     * Delete record
     *
     * @param Query $condition Delete condition
     * @return integer
     * @throws Exception
     */
    public function delete(Query $condition): int
    {
        return $this->db->query($condition->delete('table.metas'));
    }

    /**
     * Insert one record
     *
     * @param array $rows Record insert values
     * @return integer
     * @throws Exception
     */
    public function insert(array $rows): int
    {
        return $this->db->query($this->db->insert('table.metas')->rows($rows));
    }

    /**
     * 根据tag获取ID
     *
     * @param mixed $inputTags Label名
     * @return array|int
     * @throws Exception
     */
    public function scanTags($inputTags)
    {
        $tags = is_array($inputTags) ? $inputTags : [$inputTags];
        $result = [];

        foreach ($tags as $tag) {
            if (empty($tag)) {
                continue;
            }

            $row = $this->db->fetchRow($this->select()
                ->where('type = ?', 'tag')
                ->where('name = ?', $tag)->limit(1));

            if ($row) {
                $result[] = $row['mid'];
            } else {
                $slug = Common::slugName($tag);

                if ($slug) {
                    $result[] = $this->insert([
                        'name'  => $tag,
                        'slug'  => $slug,
                        'type'  => 'tag',
                        'count' => 0,
                        'order' => 0,
                    ]);
                }
            }
        }

        return is_array($inputTags) ? $result : current($result);
    }

    /**
     * Anchor ID
     *
     * @access protected
     * @return string
     */
    protected function ___theId(): string
    {
        return $this->type . '-' . $this->mid;
    }

    /**
     * @return string
     */
    protected function ___title(): string
    {
        return $this->name;
    }

    /**
     * @return array
     */
    protected function ___directory(): array
    {
        return [];
    }

    /**
     * @return string
     */
    protected function ___permalink(): string
    {
        return Router::url($this->type, $this, $this->options->index);
    }

    /**
     * @return string
     */
    protected function ___url(): string
    {
        return $this->permalink;
    }

    /**
     * @return string
     */
    protected function ___feedUrl(): string
    {
        return Router::url($this->type, $this, $this->options->feedUrl);
    }

    /**
     * @return string
     */
    protected function ___feedRssUrl(): string
    {
        return Router::url($this->type, $this, $this->options->feedRssUrl);
    }

    /**
     * @return string
     */
    protected function ___feedAtomUrl(): string
    {
        return Router::url($this->type, $this, $this->options->feedAtomUrl);
    }
}
