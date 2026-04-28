<?php

namespace Widget\Contents\Page;

use Typecho\Common;
use Typecho\Date;
use Typecho\Db\Exception as DbException;
use Typecho\Widget\Exception;
use Widget\Base\Contents;
use Widget\Contents\EditTrait;
use Widget\ActionInterface;
use Widget\Contents\PrepareEditTrait;
use Widget\Notice;
use Widget\Service;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 编辑页面组件
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
     * @access public
     * @return void
     * @throws Exception
     * @throws DbException
     */
    public function execute()
    {
        /** 必须为编辑以上权限 */
        $this->user->pass('editor');
    }

    /**
     * Publish post
     */
    public function writePage()
    {
        $contents = $this->request->from(
            'text',
            'template',
            'allowComment',
            'allowPing',
            'allowFeed',
            'slug',
            'order',
            'visibility'
        );

        $contents['title'] = $this->request->get('title', _t('Unnamed page.'));
        $contents['created'] = $this->getCreated();
        $contents['visibility'] = ('hidden' == $contents['visibility'] ? 'hidden' : 'publish');
        $contents['parent'] = $this->getParent();

        if ($this->request->is('markdown=1') && $this->options->markdown) {
            $contents['text'] = '<!--markdown-->' . $contents['text'];
        }

        $contents = self::pluginHandle()->filter('write', $contents, $this);

        if ($this->request->is('do=publish')) {
            /** Re-publish existing post */
            $contents['type'] = 'page';
            $this->publish($contents, false);

            // Complete publish plugin interface
            self::pluginHandle()->call('finishPublish', $contents, $this);

            /** Send ping */
            Service::alloc()->sendPing($this);

            /** Set notice message */
            Notice::alloc()->set(
                _t('Page "<a href="%s">%s</a>" published.', $this->permalink, $this->title),
                'success'
            );

            /** Set highlight */
            Notice::alloc()->highlight($this->theId);

            /** Page redirect */
            $this->response->redirect(Common::url('manage-pages.php'
                . ($this->parent ? '?parent=' . $this->parent : ''), $this->options->adminUrl));
        } else {
            /** Save post */
            $contents['type'] = 'page_draft';
            $draftId = $this->save($contents, false);

            // Complete publish plugin interface
            self::pluginHandle()->call('finishSave', $contents, $this);

            /** Set highlight */
            Notice::alloc()->highlight($this->cid);

            if ($this->request->isAjax()) {
                $created = new Date($this->options->time);
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
                $this->response->redirect(Common::url('write-page.php?cid=' . $this->cid, $this->options->adminUrl));
            }
        }
    }

    /**
     * 标记页面
     *
     * @throws DbException
     */
    public function markPage()
    {
        $status = $this->request->get('status');
        $statusList = [
            'publish' => _t('Public'),
            'hidden'  => _t('Hide')
        ];

        if (!isset($statusList[$status])) {
            $this->response->goBack();
        }

        $pages = $this->request->filter('int')->getArray('cid');
        $markCount = 0;

        foreach ($pages as $page) {
            // Mark plugin interface
            self::pluginHandle()->call('mark', $status, $page, $this);
            $condition = $this->db->sql()->where('cid = ?', $page);

            if ($this->db->query($condition->update('table.contents')->rows(['status' => $status]))) {
                // Handle draft
                $draft = $this->db->fetchRow($this->db->select('cid')
                    ->from('table.contents')
                    ->where('table.contents.parent = ? AND table.contents.type = ?', $page, 'revision')
                    ->limit(1));

                if (!empty($draft)) {
                    $this->db->query($this->db->update('table.contents')->rows(['status' => $status])
                        ->where('cid = ?', $draft['cid']));
                }

                // Complete mark plugin interface
                self::pluginHandle()->call('finishMark', $status, $page, $this);

                $markCount++;
            }

            unset($condition);
        }

        /** Set notice message */
        Notice::alloc()
            ->set(
                $markCount > 0 ? _t('页面已经被标记为<strong>%s</strong>', $statusList[$status]) : _t('没有页面被标记'),
                $markCount > 0 ? 'success' : 'notice'
            );

        /** Return to original page */
        $this->response->goBack();
    }

    /**
     * Delete page
     *
     * @throws DbException
     */
    public function deletePage()
    {
        $pages = $this->request->filter('int')->getArray('cid');
        $deleteCount = 0;

        foreach ($pages as $page) {
            // Remove plugin interface
            self::pluginHandle()->call('delete', $page, $this);
            $parent = $this->db->fetchObject($this->select()->where('cid = ?', $page))->parent;

            if ($this->delete($this->db->sql()->where('cid = ?', $page))) {
                /** Delete comment */
                $this->db->query($this->db->delete('table.comments')
                    ->where('cid = ?', $page));

                /** Dissociate attachment */
                $this->unAttach($page);

                /** Dissociate homepage */
                if ($this->options->frontPage == 'page:' . $page) {
                    $this->db->query($this->db->update('table.options')
                        ->rows(['value' => 'recent'])
                        ->where('name = ?', 'frontPage'));
                }

                /** Delete draft */
                $draft = $this->db->fetchRow($this->db->select('cid')
                    ->from('table.contents')
                    ->where('table.contents.parent = ? AND table.contents.type = ?', $page, 'revision')
                    ->limit(1));

                /** Delete custom fields */
                $this->deleteFields($page);

                if ($draft) {
                    $this->deleteContent($draft['cid'], false);
                    $this->deleteFields($draft['cid']);
                }

                // update parent
                $this->update(
                    ['parent' => $parent],
                    $this->db->sql()->where('parent = ?', $page)
                        ->where('type = ? OR type = ?', 'page', 'page_draft')
                );

                // Complete remove plugin interface
                self::pluginHandle()->call('finishDelete', $page, $this);

                $deleteCount++;
            }
        }

        /** Set notice message */
        Notice::alloc()
            ->set(
                $deleteCount > 0 ? _t('Pages deleted.') : _t('No page to delete.'),
                $deleteCount > 0 ? 'success' : 'notice'
            );

        /** Return to original page */
        $this->response->goBack();
    }

    /**
     * Delete page所属草稿
     *
     * @throws DbException
     */
    public function deletePageDraft()
    {
        $pages = $this->request->filter('int')->getArray('cid');
        $deleteCount = 0;

        foreach ($pages as $page) {
            /** Delete draft */
            $draft = $this->db->fetchRow($this->db->select('cid')
                ->from('table.contents')
                ->where('table.contents.parent = ? AND table.contents.type = ?', $page, 'revision')
                ->limit(1));

            if ($draft) {
                $this->deleteContent($draft['cid'], false);
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
     * 页面Sort
     *
     * @throws DbException
     */
    public function sortPage()
    {
        $pages = $this->request->filter('int')->getArray('cid');

        if ($pages) {
            foreach ($pages as $sort => $cid) {
                $this->db->query($this->db->update('table.contents')->rows(['order' => $sort + 1])
                    ->where('cid = ?', $cid));
            }
        }

        if (!$this->request->isAjax()) {
            /** Redirect to original page */
            $this->response->goBack();
        } else {
            $this->response->throwJson(['success' => 1, 'message' => _t('Page sorted.')]);
        }
    }

    /**
     * @return $this
     * @throws DbException
     * @throws Exception
     */
    public function prepare(): self
    {
        return $this->prepareEdit('page', true, _t('Page does not exist.'));
    }

    /**
     * Bind action
     *
     * @return void
     * @throws DbException
     * @throws Exception
     */
    public function action()
    {
        $this->security->protect();
        $this->on($this->request->is('do=publish') || $this->request->is('do=save'))
            ->prepare()->writePage();
        $this->on($this->request->is('do=delete'))->deletePage();
        $this->on($this->request->is('do=mark'))->markPage();
        $this->on($this->request->is('do=deleteDraft'))->deletePageDraft();
        $this->on($this->request->is('do=sort'))->sortPage();
        $this->response->redirect($this->options->adminUrl);
    }

    /**
     * Get page title
     *
     * @return string
     */
    public function getMenuTitle(): string
    {
        $this->prepare();

        if ($this->have()) {
            return _t('Edit %s', $this->title);
        }

        if ($this->request->is('parent')) {
            $page = $this->db->fetchRow($this->select()
                ->where('table.contents.type = ? OR table.contents.type', 'page', 'page_draft')
                ->where('table.contents.cid = ?', $this->request->filter('int')->get('parent')));

            if (!empty($page)) {
                return _t('新增 %s 的子页面', $page['title']);
            }
        }

        throw new Exception(_t('Page does not exist.'), 404);
    }


    /**
     * @return int
     */
    public function getParent(): int
    {
        if ($this->request->is('parent')) {
            $parent = $this->request->filter('int')->get('parent');

            if (!$this->have() || $this->cid != $parent) {
                $parentPage = $this->db->fetchRow($this->select()
                    ->where('table.contents.type = ? OR table.contents.type = ?', 'page', 'page_draft')
                    ->where('table.contents.cid = ?', $parent));

                if (!empty($parentPage)) {
                    return $parent;
                }
            }
        }

        return 0;
    }

    /**
     * @return string
     */
    protected function getThemeFieldsHook(): string
    {
        return 'themePageFields';
    }
}
