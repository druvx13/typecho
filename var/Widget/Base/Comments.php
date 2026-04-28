<?php

namespace Widget\Base;

use Typecho\Common;
use Typecho\Date;
use Typecho\Db\Exception;
use Typecho\Db\Query;
use Typecho\Router;
use Typecho\Router\ParamsDelegateInterface;
use Utils\AutoP;
use Utils\Markdown;
use Widget\Base;
use Widget\Contents\From;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 评论基类
 *
 * @property int $coid
 * @property int $cid
 * @property int $created
 * @property string author
 * @property int $authorId
 * @property int $ownerId
 * @property string $mail
 * @property string $url
 * @property string $ip
 * @property string $agent
 * @property string $text
 * @property string $type
 * @property string status
 * @property int $parent
 * @property int $commentPage
 * @property Date $date
 * @property string $dateWord
 * @property string $theId
 * @property Contents $parentContent
 * @property string $title
 * @property string $permalink
 * @property string $content
 */
class Comments extends Base implements QueryInterface, RowFilterInterface, PrimaryKeyInterface, ParamsDelegateInterface
{
    /**
     * @return string Get primary key
     */
    public function getPrimaryKey(): string
    {
        return 'coid';
    }

    /**
     * @param string $key
     * @return string
     */
    public function getRouterParam(string $key): string
    {
        switch ($key) {
            case 'permalink':
                return $this->parentContent->path;
            case 'commentPage':
                return $this->commentPage;
            default:
                return '{' . $key . '}';
        }
    }

    /**
     * 增加评论
     *
     * @param array $rows Comment structure array
     * @return integer
     * @throws Exception
     */
    public function insert(array $rows): int
    {
        /** Build insert structure */
        $insertStruct = [
            'cid'      => $rows['cid'],
            'created'  => empty($rows['created']) ? $this->options->time : $rows['created'],
            'author'   => Common::strBy($rows['author'] ?? null),
            'authorId' => empty($rows['authorId']) ? 0 : $rows['authorId'],
            'ownerId'  => empty($rows['ownerId']) ? 0 : $rows['ownerId'],
            'mail'     => Common::strBy($rows['mail'] ?? null),
            'url'      => Common::strBy($rows['url'] ?? null),
            'ip'       => Common::strBy($rows['ip'] ?? null, $this->request->getIp()),
            'agent'    => Common::strBy($rows['agent'] ?? null, $this->request->getAgent()),
            'text'     => Common::strBy($rows['text'] ?? null),
            'type'     => Common::strBy($rows['type'] ?? null, 'comment'),
            'status'   => Common::strBy($rows['status'] ?? null, 'approved'),
            'parent'   => empty($rows['parent']) ? 0 : $rows['parent'],
        ];

        if (!empty($rows['coid'])) {
            $insertStruct['coid'] = $rows['coid'];
        }

        /** 过长的客户端String要截断 */
        if (Common::strLen($insertStruct['agent']) > 511) {
            $insertStruct['agent'] = Common::subStr($insertStruct['agent'], 0, 511, '');
        }

        /** Insert partial data first */
        $insertId = $this->db->query($this->db->insert('table.comments')->rows($insertStruct));

        /** Update comment count */
        $num = $this->db->fetchObject($this->db->select(['COUNT(coid)' => 'num'])->from('table.comments')
            ->where('status = ? AND cid = ?', 'approved', $rows['cid']))->num;

        $this->db->query($this->db->update('table.contents')->rows(['commentsNum' => $num])
            ->where('cid = ?', $rows['cid']));

        return $insertId;
    }

    /**
     * Update comment
     *
     * @param array $rows Comment structure array
     * @param Query $condition Query object
     * @return integer
     * @throws Exception
     */
    public function update(array $rows, Query $condition): int
    {
        /** Get content primary key */
        $updateCondition = clone $condition;
        $updateComment = $this->db->fetchObject($condition->select('cid')->from('table.comments')->limit(1));

        if ($updateComment) {
            $cid = $updateComment->cid;
        } else {
            return 0;
        }

        /** Build insert structure */
        $preUpdateStruct = [
            'author' => Common::strBy($rows['author'] ?? null),
            'mail'   => Common::strBy($rows['mail'] ?? null),
            'url'    => Common::strBy($rows['url'] ?? null),
            'text'   => Common::strBy($rows['text'] ?? null),
            'status' => Common::strBy($rows['status'] ?? null, 'approved'),
        ];

        $updateStruct = [];
        foreach ($rows as $key => $val) {
            if ((array_key_exists($key, $preUpdateStruct))) {
                $updateStruct[$key] = $preUpdateStruct[$key];
            }
        }

        /** Update creation time */
        if (!empty($rows['created'])) {
            $updateStruct['created'] = $rows['created'];
        }

        /** Update comment数据 */
        $updateRows = $this->db->query($updateCondition->update('table.comments')->rows($updateStruct));

        /** Update comment count */
        $num = $this->db->fetchObject($this->db->select(['COUNT(coid)' => 'num'])->from('table.comments')
            ->where('status = ? AND cid = ?', 'approved', $cid))->num;

        $this->db->query($this->db->update('table.contents')->rows(['commentsNum' => $num])
            ->where('cid = ?', $cid));

        return $updateRows;
    }

    /**
     * Delete data
     *
     * @param Query $condition Query object
     * @return integer
     * @throws Exception
     */
    public function delete(Query $condition): int
    {
        /** Get content primary key */
        $deleteCondition = clone $condition;
        $deleteComment = $this->db->fetchObject($condition->select('cid')->from('table.comments')->limit(1));

        if ($deleteComment) {
            $cid = $deleteComment->cid;
        } else {
            return 0;
        }

        /** 删除评论数据 */
        $deleteRows = $this->db->query($deleteCondition->delete('table.comments'));

        /** Update comment count */
        $num = $this->db->fetchObject($this->db->select(['COUNT(coid)' => 'num'])->from('table.comments')
            ->where('status = ? AND cid = ?', 'approved', $cid))->num;

        $this->db->query($this->db->update('table.contents')->rows(['commentsNum' => $num])
            ->where('cid = ?', $cid));

        return $deleteRows;
    }

    /**
     * 按照条件计算评论数量
     *
     * @param Query $condition Query object
     * @return integer
     * @throws Exception
     */
    public function size(Query $condition): int
    {
        return $this->db->fetchObject($condition->select(['COUNT(coid)' => 'num'])->from('table.comments'))->num;
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
        /** Handle default empty value */
        $row['author'] = $row['author'] ?? '';
        $row['mail'] = $row['mail'] ?? '';
        $row['url'] = $row['url'] ?? '';
        $row['ip'] = $row['ip'] ?? '';
        $row['agent'] = $row['agent'] ?? '';
        $row['text'] = $row['text'] ?? '';

        $row['date'] = new Date($row['created']);
        return Comments::pluginHandle()->filter('filter', $row, $this);
    }

    /**
     * Output post publication date
     *
     * @param string|null $format Date format
     */
    public function date(?string $format = null)
    {
        echo $this->date->format(empty($format) ? $this->options->commentDateFormat : $format);
    }

    /**
     * 输出作者相关
     *
     * @param boolean|null $autoLink 是否自动加上链接
     * @param boolean|null $noFollow 是否加上nofollowLabel
     */
    public function author(?bool $autoLink = null, ?bool $noFollow = null)
    {
        $autoLink = (null === $autoLink) ? $this->options->commentsShowUrl : $autoLink;
        $noFollow = (null === $noFollow) ? $this->options->commentsUrlNofollow : $noFollow;

        if ($this->url && $autoLink) {
            echo '<a href="' . Common::safeUrl($this->url) . '"'
                . ($noFollow ? ' rel="external nofollow"' : null) . '>' . $this->author . '</a>';
        } else {
            echo $this->author;
        }
    }

    /**
     * Call Gravatar to output user avatar
     *
     * @param integer $size Avatar size
     * @param string|null $default Default avatar
     */
    public function gravatar(int $size = 32, ?string $default = null, $highRes = false)
    {
        if ($this->options->commentsAvatar && 'comment' == $this->type) {
            $rating = $this->options->commentsAvatarRating;

            Comments::pluginHandle()->trigger($plugged)->call('gravatar', $size, $rating, $default, $this);
            if (!$plugged) {
                $url = Common::gravatarUrl($this->mail, $size, $rating, $default, $this->request->isSecure());
                $srcset = '';

                if ($highRes) {
                    $url2x = Common::gravatarUrl($this->mail, $size * 2, $rating, $default, $this->request->isSecure());
                    $url3x = Common::gravatarUrl($this->mail, $size * 3, $rating, $default, $this->request->isSecure());
                    $srcset = ' srcset="' . $url2x . ' 2x, ' . $url3x . ' 3x"';
                }

                echo '<img class="avatar" loading="lazy" src="' . $url . '"' . $srcset . ' alt="' .
                    $this->author . '" width="' . $size . '" height="' . $size . '" />';
            }
        }
    }

    /**
     * 输出评论摘要
     *
     * @param integer $length Excerpt length
     * @param string $trim Excerpt suffix
     */
    public function excerpt(int $length = 100, string $trim = '...')
    {
        echo Common::subStr(strip_tags($this->content), 0, $length, $trim);
    }

    /**
     * 输出邮箱地址
     *
     * @param bool $link
     * @return void
     */
    public function mail(bool $link = false)
    {
        $mail = htmlspecialchars($this->mail);
        echo $link ? 'mailto:' . $mail : $mail;
    }

    /**
     * Get query object
     *
     * @param mixed $fields
     * @return Query
     */
    public function select(...$fields): Query
    {
        return $this->db->select(...$fields)->from('table.comments');
    }

    /**
     * markdown
     *
     * @param string|null $text
     * @return string|null
     */
    public function markdown(?string $text): ?string
    {
        $html = Comments::pluginHandle()->trigger($parsed)->filter('markdown', $text);

        if (!$parsed) {
            $html = Markdown::convert($text);
        }

        return $html;
    }

    /**
     * autoP
     *
     * @param string|null $text
     * @return string|null
     */
    public function autoP(?string $text): ?string
    {
        $html = Comments::pluginHandle()->trigger($parsed)->filter('autoP', $text);

        if (!$parsed) {
            static $parser;

            if (empty($parser)) {
                $parser = new AutoP();
            }

            $html = $parser->parse($text);
        }

        return $html;
    }

    /**
     * Get current content structure
     *
     * @return Contents
     */
    protected function ___parentContent(): Contents
    {
        return From::allocWithAlias($this->cid, ['cid' => $this->cid]);
    }

    /**
     * 获取当前评论Title
     *
     * @return string|null
     */
    protected function ___title(): ?string
    {
        return $this->parentContent->title;
    }

    /**
     * 获取当前评论页码
     *
     * @return int
     */
    protected function ___commentPage(): int
    {
        if ($this->options->commentsPageBreak) {
            $coid = $this->coid;
            $parent = $this->parent;

            while ($parent > 0 && $this->options->commentsThreaded) {
                $parentRows = $this->db->fetchRow($this->db->select('parent')->from('table.comments')
                    ->where('coid = ? AND status = ?', $parent, 'approved')->limit(1));

                if (!empty($parentRows)) {
                    $coid = $parent;
                    $parent = $parentRows['parent'];
                } else {
                    break;
                }
            }

            $select = $this->db->select('coid', 'parent')
                ->from('table.comments')
                ->where(
                    'cid = ? AND (status = ? OR coid = ?)',
                    $this->cid,
                    'approved',
                    $this->status !== 'approved' ? $this->coid : 0
                )
                ->where('coid ' . ('DESC' == $this->options->commentsOrder ? '>=' : '<=') . ' ?', $coid)
                ->order('coid');

            if ($this->options->commentsShowCommentOnly) {
                $select->where('type = ?', 'comment');
            }

            $comments = $this->db->fetchAll($select);

            $commentsMap = [];
            $total = 0;

            foreach ($comments as $comment) {
                $commentsMap[$comment['coid']] = $comment['parent'];

                if (0 == $comment['parent'] || !isset($commentsMap[$comment['parent']])) {
                    $total++;
                }
            }

            return ceil($total / $this->options->commentsPageSize);
        }

        return 0;
    }

    /**
     * 获取当前评论链接
     *
     * @return string
     * @throws Exception
     */
    protected function ___permalink(): string
    {
        if ($this->options->commentsPageBreak) {
            return Router::url(
                'comment_page',
                $this,
                $this->options->index
            ) . '#' . $this->theId;
        }

        return $this->parentContent->permalink . '#' . $this->theId;
    }

    /**
     * 获取当前Comment content
     *
     * @return string|null
     */
    protected function ___content(): ?string
    {
        $text = $this->parentContent->hidden ? _t('The content is hidden.') : $this->text;

        $text = Comments::pluginHandle()->trigger($plugged)->filter('content', $text, $this);
        if (!$plugged) {
            $text = $this->options->commentsMarkdown ? $this->markdown($text)
                : $this->autoP($text);
        }

        $text = Comments::pluginHandle()->filter('contentEx', $text, $this);
        return Common::stripTags($text, '<p><br>' . $this->options->commentsHTMLTagAllowed);
    }

    /**
     * 输出词义化Sun期
     *
     * @return string
     */
    protected function ___dateWord(): string
    {
        return $this->date->word();
    }

    /**
     * Anchor ID
     *
     * @return string
     */
    protected function ___theId(): string
    {
        return $this->type . '-' . $this->coid;
    }
}
