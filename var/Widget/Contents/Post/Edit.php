<?php

namespace Widget\Contents\Post;

use Typecho\Common;
use Typecho\Widget\Exception;
use Widget\Base\Contents;
use Widget\Base\Metas;
use Widget\ActionInterface;
use Typecho\Db\Exception as DbException;
use Typecho\Date as TypechoDate;
use Widget\Contents\EditTrait;
use Widget\Contents\PrepareEditTrait;
use Widget\Notice;
use Widget\Service;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * Post editing widget
 *
 * @property-read array $draft
 */
class Edit extends Contents implements ActionInterface
{
    use PrepareEditTrait;
    use EditTrait;

    /**
     * Execute action
     *
     * @throws Exception|DbException
     */
    public function execute()
    {
        /** Must be contributor or higher */
        $this->user->pass('contributor');
    }

    /**
     * Publish post
     */
    public function writePost()
    {
        $contents = $this->request->from(
            'password',
            'allowComment',
            'allowPing',
            'allowFeed',
            'slug',
            'tags',
            'text',
            'visibility'
        );

        $contents['category'] = $this->request->getArray('category');
        $contents['title'] = $this->request->get('title', _t('Unnamed document'));
        $contents['created'] = $this->getCreated();

        if ($this->request->is('markdown=1') && $this->options->markdown) {
            $contents['text'] = '<!--markdown-->' . $contents['text'];
        }

        $contents = self::pluginHandle()->filter('write', $contents, $this);

        if ($this->request->is('do=publish')) {
            /** Re-publish existing post */
            $contents['type'] = 'post';
            $this->publish($contents);

            // Complete publish plugin interface
            self::pluginHandle()->call('finishPublish', $contents, $this);

            /** Send ping */
            $trackback = array_filter(
                array_unique(preg_split("/(\r|\n|\r\n)/", trim($this->request->get('trackback', ''))))
            );
            Service::alloc()->sendPing($this, $trackback);

            /** Set notice message */
            Notice::alloc()->set('post' == $this->type ?
                _t('Post "<a href="%s">%s</a>" published.', $this->permalink, $this->title) :
                _t('Post "%s" under review.', $this->title), 'success');

            /** Set highlight */
            Notice::alloc()->highlight($this->theId);

            /** Get page offset */
            $pageQuery = $this->getPageOffsetQuery($this->cid);

            /** Page redirect */
            $this->response->redirect(Common::url('manage-posts.php?' . $pageQuery, $this->options->adminUrl));
        } else {
            /** Save post */
            $contents['type'] = 'post_draft';
            $draftId = $this->save($contents);

            // 完成保存Plugin interface
            self::pluginHandle()->call('finishSave', $contents, $this);

            /** Set highlight */
            Notice::alloc()->highlight($this->cid);

            if ($this->request->isAjax()) {
                $created = new TypechoDate();
                $this->response->throwJson([
                    'success' => 1,
                    'time'    => $created->format('H:i:s A'),
                    'cid'     => $this->cid,
                    'draftId' => $draftId
                ]);
            } else {
                /** Set notice message */
                Notice::alloc()->set(_t('Draft "%s" saved.', $this->title), 'success');

                /** Return to original page */
                $this->response->redirect(Common::url('write-post.php?cid=' . $this->cid, $this->options->adminUrl));
            }
        }
    }

    /**
     * Get page offset URL query
     *
     * @param integer $cid 文章id
     * @param string|null $status Status
     * @return string
     * @throws DbException
     */
    protected function getPageOffsetQuery(int $cid, ?string $status = null): string
    {
        return 'page=' . $this->getPageOffset(
            'cid',
            $cid,
            'post',
            $status,
            $this->request->is('__typecho_all_posts=on') ? 0 : $this->user->uid
        );
    }

    /**
     * 标记文章
     *
     * @throws DbException
     */
    public function markPost()
    {
        $status = $this->request->get('status');
        $statusList = [
            'publish' => _t('Public'),
            'private' => _t('Private'),
            'hidden'  => _t('Hide'),
            'waiting' => _t('Awaiting approval')
        ];

        if (!isset($statusList[$status])) {
            $this->response->goBack();
        }

        $posts = $this->request->filter('int')->getArray('cid');
        $markCount = 0;

        foreach ($posts as $post) {
            // Mark plugin interface
            self::pluginHandle()->call('mark', $status, $post, $this);

            $condition = $this->db->sql()->where('cid = ?', $post);
            $postObject = $this->db->fetchObject($this->db->select('status', 'type')
                ->from('table.contents')->where('cid = ? AND (type = ? OR type = ?)', $post, 'post', 'post_draft'));

            if ($this->isWriteable(clone $condition) && count((array)$postObject)) {

                /** 标记状态 */
                $this->db->query($condition->update('table.contents')->rows(['status' => $status]));

                // 刷新Metas
                if ($postObject->type == 'post') {
                    $op = null;

                    if ($status == 'publish' && $postObject->status != 'publish') {
                        $op = '+';
                    } elseif ($status != 'publish' && $postObject->status == 'publish') {
                        $op = '-';
                    }

                    if (!empty($op)) {
                        $metas = $this->db->fetchAll(
                            $this->db->select()->from('table.relationships')->where('cid = ?', $post)
                        );
                        foreach ($metas as $meta) {
                            $this->db->query($this->db->update('table.metas')
                                ->expression('count', 'count ' . $op . ' 1')
                                ->where('mid = ? AND (type = ? OR type = ?)', $meta['mid'], 'category', 'tag'));
                        }
                    }
                }

                // Handle draft
                $draft = $this->db->fetchRow($this->db->select('cid')
                    ->from('table.contents')
                    ->where('table.contents.parent = ? AND table.contents.type = ?', $post, 'revision')
                    ->limit(1));

                if (!empty($draft)) {
                    $this->db->query($this->db->update('table.contents')->rows(['status' => $status])
                        ->where('cid = ?', $draft['cid']));
                }

                // Complete mark plugin interface
                self::pluginHandle()->call('finishMark', $status, $post, $this);

                $markCount++;
            }

            unset($condition);
        }

        /** Set notice message */
        Notice::alloc()
            ->set(
                $markCount > 0 ? _t('文章已经被标记为<strong>%s</strong>', $statusList[$status]) : _t('没有文章被标记'),
                $markCount > 0 ? 'success' : 'notice'
            );

        /** Return to original page */
        $this->response->goBack();
    }

    /**
     * Delete post
     *
     * @throws DbException
     */
    public function deletePost()
    {
        $posts = $this->request->filter('int')->getArray('cid');
        $deleteCount = 0;

        foreach ($posts as $post) {
            // Remove plugin interface
            self::pluginHandle()->call('delete', $post, $this);

            $condition = $this->db->sql()->where('cid = ?', $post);
            $postObject = $this->db->fetchObject($this->db->select('status', 'type')
                ->from('table.contents')->where('cid = ? AND (type = ? OR type = ?)', $post, 'post', 'post_draft'));

            if ($this->isWriteable(clone $condition) && count((array)$postObject) && $this->delete($condition)) {

                /** Delete category */
                $this->setCategories($post, [], 'publish' == $postObject->status
                    && 'post' == $postObject->type);

                /** Delete label */
                $this->setTags($post, null, 'publish' == $postObject->status
                    && 'post' == $postObject->type);

                /** Delete comment */
                $this->db->query($this->db->delete('table.comments')
                    ->where('cid = ?', $post));

                /** Dissociate attachment */
                $this->unAttach($post);

                /** Delete draft */
                $draft = $this->db->fetchRow($this->db->select('cid')
                    ->from('table.contents')
                    ->where('table.contents.parent = ? AND table.contents.type = ?', $post, 'revision')
                    ->limit(1));

                /** Delete custom fields */
                $this->deleteFields($post);

                if ($draft) {
                    $this->deleteContent($draft['cid']);
                    $this->deleteFields($draft['cid']);
                }

                // Complete remove plugin interface
                self::pluginHandle()->call('finishDelete', $post, $this);

                $deleteCount++;
            }

            unset($condition);
        }

        // 清理Label
        if ($deleteCount > 0) {
            Metas::alloc()->clearTags();
        }

        /** Set notice message */
        Notice::alloc()->set(
            $deleteCount > 0 ? _t('Post deleted.') : _t('No post to delete.'),
            $deleteCount > 0 ? 'success' : 'notice'
        );

        /** Return to original page */
        $this->response->goBack();
    }

    /**
     * Delete post所属草稿
     *
     * @throws DbException
     */
    public function deletePostDraft()
    {
        $posts = $this->request->filter('int')->getArray('cid');
        $deleteCount = 0;

        foreach ($posts as $post) {
            /** Delete draft */
            $draft = $this->db->fetchRow($this->db->select('cid')
                ->from('table.contents')
                ->where('table.contents.parent = ? AND table.contents.type = ?', $post, 'revision')
                ->limit(1));

            if ($draft) {
                $this->deleteContent($draft['cid']);
                $this->deleteFields($draft['cid']);
                $deleteCount++;
            }
        }

        /** Set notice message */
        Notice::alloc()
            ->set(
                $deleteCount > 0 ? _t('Drafts deleted.') : _t('No draft to delete.'),
                $deleteCount > 0 ? 'success' : 'notice'
            );

        /** Return to original page */
        $this->response->goBack();
    }

    /**
     * @return $this
     * @throws DbException
     * @throws Exception
     */
    public function prepare(): self
    {
        return $this->prepareEdit('post', true, _t('Post does not exist.'));
    }

    /**
     * Bind action
     *
     * @throws Exception|DbException
     */
    public function action()
    {
        $this->security->protect();
        $this->on($this->request->is('do=publish') || $this->request->is('do=save'))
            ->prepare()->writePost();
        $this->on($this->request->is('do=delete'))->deletePost();
        $this->on($this->request->is('do=mark'))->markPost();
        $this->on($this->request->is('do=deleteDraft'))->deletePostDraft();

        $this->response->redirect($this->options->adminUrl);
    }

    /**
     * @return string
     */
    protected function getThemeFieldsHook(): string
    {
        return 'themePostFields';
    }
}
