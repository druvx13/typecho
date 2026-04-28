<?php

namespace Widget\Base;

use Typecho\Common;
use Typecho\Config;
use Typecho\Db\Exception;
use Typecho\Db\Query;
use Typecho\Router;
use Typecho\Router\ParamsDelegateInterface;
use Widget\Base;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 用户抽象类
 *
 * @property int $uid
 * @property string $name
 * @property string $password
 * @property string $mail
 * @property string $url
 * @property string $screenName
 * @property int $created
 * @property int $activated
 * @property int $logged
 * @property string $group
 * @property string $authCode
 * @property-read Config $personalOptions
 * @property-read string $permalink
 * @property-read string $feedUrl
 * @property-read string $feedRssUrl
 * @property-read string $feedAtomUrl
 */
class Users extends Base implements QueryInterface, RowFilterInterface, PrimaryKeyInterface, ParamsDelegateInterface
{
    /**
     * @return string Get primary key
     */
    public function getPrimaryKey(): string
    {
        return 'uid';
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
        return Users::pluginHandle()->filter('filter', $row, $this);
    }

    /**
     * @param string $key
     * @return string
     */
    public function getRouterParam(string $key): string
    {
        switch ($key) {
            case 'uid':
                return $this->uid;
            default:
                return '{' . $key . '}';
        }
    }

    /**
     * Query method
     *
     * @param mixed $fields
     * @return Query
     * @throws Exception
     */
    public function select(...$fields): Query
    {
        return $this->db->select(...$fields)->from('table.users');
    }

    /**
     * Get total record count
     *
     * @param Query $condition Query object
     * @return integer
     * @throws Exception
     */
    public function size(Query $condition): int
    {
        return $this->db->fetchObject($condition->select(['COUNT(uid)' => 'num'])->from('table.users'))->num;
    }

    /**
     * Add record method
     *
     * @param array $rows Field values
     * @return integer
     * @throws Exception
     */
    public function insert(array $rows): int
    {
        return $this->db->query($this->db->insert('table.users')->rows($rows));
    }

    /**
     * Update record method
     *
     * @param array $rows Field values
     * @param Query $condition Query object
     * @return integer
     * @throws Exception
     */
    public function update(array $rows, Query $condition): int
    {
        return $this->db->query($condition->update('table.users')->rows($rows));
    }

    /**
     * Delete record method
     *
     * @param Query $condition Query object
     * @return integer
     * @throws Exception
     */
    public function delete(Query $condition): int
    {
        return $this->db->query($condition->delete('table.users'));
    }

    /**
     * Call Gravatar to output user avatar
     *
     * @param integer $size Avatar size
     * @param string $rating 头像评级
     * @param string|null $default Default avatar
     * @param string|null $class 默认css class
     */
    public function gravatar(int $size = 40, string $rating = 'X', ?string $default = null, ?string $class = null)
    {
        $url = Common::gravatarUrl($this->mail, $size, $rating, $default, $this->request->isSecure());
        echo '<img' . (empty($class) ? '' : ' class="' . $class . '"') . ' src="' . $url . '" alt="' .
            $this->screenName . '" width="' . $size . '" height="' . $size . '" />';
    }

    /**
     * @return string
     */
    protected function ___permalink(): string
    {
        return Router::url('author', $this, $this->options->index);
    }

    /**
     * @return string
     */
    protected function ___feedUrl(): string
    {
        return Router::url('author', $this, $this->options->feedUrl);
    }

    /**
     * @return string
     */
    protected function ___feedRssUrl(): string
    {
        return Router::url('author', $this, $this->options->feedRssUrl);
    }

    /**
     * @return string
     */
    protected function ___feedAtomUrl(): string
    {
        return Router::url('author', $this, $this->options->feedAtomUrl);
    }

    /**
     * personalOptions
     *
     * @return Config
     * @throws Exception
     */
    protected function ___personalOptions(): Config
    {
        $rows = $this->db->fetchAll($this->db->select()
            ->from('table.options')->where('user = ?', $this->uid));
        $options = [];
        foreach ($rows as $row) {
            $options[$row['name']] = $row['value'];
        }

        return new Config($options);
    }
}
