<?php

namespace Widget\Metas\Tag;

use Typecho\Common;
use Typecho\Db\Exception;
use Typecho\Widget\Helper\Form;
use Widget\Base\Metas;
use Widget\ActionInterface;
use Widget\Metas\EditTrait;
use Widget\Notice;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * Label编辑组件
 *
 * @author qining
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Edit extends Metas implements ActionInterface
{
    use EditTrait;

    /**
     * Entry point
     */
    public function execute()
    {
        /** Editor or higher permission */
        $this->user->pass('editor');
    }

    /**
     * 判断Label是否存在
     *
     * @param integer $mid Label主键
     * @return boolean
     * @throws Exception
     */
    public function tagExists(int $mid): bool
    {
        $tag = $this->db->fetchRow($this->db->select()
            ->from('table.metas')
            ->where('type = ?', 'tag')
            ->where('mid = ?', $mid)->limit(1));

        return isset($tag);
    }

    /**
     * 判断Label名称是否存在
     *
     * @param string $name Label名称
     * @return boolean
     * @throws Exception
     */
    public function nameExists(string $name): bool
    {
        $select = $this->db->select()
            ->from('table.metas')
            ->where('type = ?', 'tag')
            ->where('name = ?', $name)
            ->limit(1);

        if ($this->request->is('mid')) {
            $select->where('mid <> ?', $this->request->filter('int')->get('mid'));
        }

        $tag = $this->db->fetchRow($select);
        return !$tag;
    }

    /**
     * 判断Label名转换到缩略名后是否合法
     *
     * @param string $name Label名
     * @return boolean
     * @throws Exception
     */
    public function nameToSlug(string $name): bool
    {
        if (empty($this->request->slug)) {
            $slug = Common::slugName($name);
            if (empty($slug) || !$this->slugExists($name)) {
                return false;
            }
        }

        return true;
    }

    /**
     * 判断Label缩略名是否存在
     *
     * @param string $slug Slug
     * @return boolean
     * @throws Exception
     */
    public function slugExists(string $slug): bool
    {
        $select = $this->db->select()
            ->from('table.metas')
            ->where('type = ?', 'tag')
            ->where('slug = ?', Common::slugName($slug))
            ->limit(1);

        if ($this->request->is('mid')) {
            $select->where('mid <> ?', $this->request->get('mid'));
        }

        $tag = $this->db->fetchRow($select);
        return !$tag;
    }

    /**
     * 插入Label
     *
     * @throws Exception
     */
    public function insertTag()
    {
        if ($this->form('insert')->validate()) {
            $this->response->goBack();
        }

        /** Fetch data */
        $tag = $this->request->from('name', 'slug');
        $tag['type'] = 'tag';
        $tag['slug'] = Common::slugName(Common::strBy($tag['slug'] ?? null, $tag['name']));

        /** Insert data */
        $tag['mid'] = $this->insert($tag);
        $this->push($tag);

        /** Set highlight */
        Notice::alloc()->highlight($this->theId);

        /** Notice message */
        Notice::alloc()->set(
            _t('Tag <a href="%s">%s</a> added.', $this->permalink, $this->name),
            'success'
        );

        /** Redirect to original page */
        $this->response->redirect(Common::url('manage-tags.php', $this->options->adminUrl));
    }

    /**
     * Generate form
     *
     * @param string|null $action Form action
     * @return Form
     * @throws Exception
     */
    public function form(?string $action = null): Form
    {
        /** Build form */
        $form = new Form($this->security->getIndex('/action/metas-tag-edit'), Form::POST_METHOD);

        /** Label名称 */
        $name = new Form\Element\Text(
            'name',
            null,
            null,
            _t('Label名称') . ' *',
            _t('This is the tag name shown in website.')
        );
        $form->addInput($name);

        /** Label缩略名 */
        $slug = new Form\Element\Text(
            'slug',
            null,
            null,
            _t('Tag abbreviation'),
            _t('Abbreviation is used to create friendly URL. If you leave it blank, tag name will be used by default.')
        );
        $form->addInput($slug);

        /** Label动作 */
        $do = new Form\Element\Hidden('do');
        $form->addInput($do);

        /** Label主键 */
        $mid = new Form\Element\Hidden('mid');
        $form->addInput($mid);

        /** Submit button */
        $submit = new Form\Element\Submit();
        $submit->input->setAttribute('class', 'btn primary');
        $form->addItem($submit);

        if ($this->request->is('mid') && 'insert' != $action) {
            /** Update mode */
            $meta = $this->db->fetchRow($this->select()
                ->where('mid = ?', $this->request->get('mid'))
                ->where('type = ?', 'tag')->limit(1));

            if (!$meta) {
                $this->response->redirect(Common::url('manage-tags.php', $this->options->adminUrl));
            }

            $name->value($meta['name']);
            $slug->value($meta['slug']);
            $do->value('update');
            $mid->value($meta['mid']);
            $submit->value(_t('Edit tags.'));
            $_action = 'update';
        } else {
            $do->value('insert');
            $submit->value(_t('Add a new tag.'));
            $_action = 'insert';
        }

        if (empty($action)) {
            $action = $_action;
        }

        /** Add validation rule to form */
        if ('insert' == $action || 'update' == $action) {
            $name->addRule('required', _t('You must enter a name for tag.'));
            $name->addRule([$this, 'nameExists'], _t('Tag already exists.'));
            $name->addRule([$this, 'nameToSlug'], _t('Tag name cannot be converted to an abbreviation.'));
            $name->addRule('xssCheck', _t('Please do not use special characters in tag names'));
            $slug->addRule([$this, 'slugExists'], _t('Abbreviation already exists.'));
            $slug->addRule('xssCheck', _t('Please do not include special characters in the thumbnail name.'));
        }

        if ('update' == $action) {
            $mid->addRule('required', _t('Tag key does not exist.'));
            $mid->addRule([$this, 'tagExists'], _t('This tag does not exist.'));
        }

        return $form;
    }

    /**
     * 更新Label
     *
     * @throws Exception
     */
    public function updateTag()
    {
        if ($this->form('update')->validate()) {
            $this->response->goBack();
        }

        /** Fetch data */
        $tag = $this->request->from('name', 'slug', 'mid');
        $tag['type'] = 'tag';
        $tag['slug'] = Common::slugName(Common::strBy($tag['slug'] ?? null, $tag['name']));

        /** Update data */
        $this->update($tag, $this->db->sql()->where('mid = ?', $this->request->filter('int')->get('mid')));
        $this->push($tag);

        /** Set highlight */
        Notice::alloc()->highlight($this->theId);

        /** Notice message */
        Notice::alloc()->set(
            _t('Tag <a href="%s">%s</a> updated.', $this->permalink, $this->name),
            'success'
        );

        /** Redirect to original page */
        $this->response->redirect(Common::url('manage-tags.php', $this->options->adminUrl));
    }

    /**
     * 删除Label
     *
     * @throws Exception
     */
    public function deleteTag()
    {
        $tags = $this->request->filter('int')->getArray('mid');
        $deleteCount = 0;

        if ($tags) {
            foreach ($tags as $tag) {
                if ($this->delete($this->db->sql()->where('mid = ?', $tag))) {
                    $this->db->query($this->db->delete('table.relationships')->where('mid = ?', $tag));
                    $deleteCount++;
                }
            }
        }

        /** Notice message */
        Notice::alloc()->set(
            $deleteCount > 0 ? _t('Tag  deleted.') : _t('No tag to be deleted.'),
            $deleteCount > 0 ? 'success' : 'notice'
        );

        /** Redirect to original page */
        $this->response->redirect(Common::url('manage-tags.php', $this->options->adminUrl));
    }

    /**
     * 合并Label
     *
     * @throws Exception
     */
    public function mergeTag()
    {
        if (empty($this->request->merge)) {
            Notice::alloc()->set(_t('Please choose tags to be combined.'));
            $this->response->goBack();
        }

        $merge = $this->scanTags($this->request->get('merge'));
        if (empty($merge)) {
            Notice::alloc()->set(_t('The name for combining tags is invalid.'), 'error');
            $this->response->goBack();
        }

        $tags = $this->request->filter('int')->getArray('mid');

        if ($tags) {
            $this->merge($merge, 'tag', $tags);

            /** Notice message */
            Notice::alloc()->set(_t('Tags are combined.'), 'success');
        } else {
            Notice::alloc()->set(_t('No tag has been selected.'));
        }

        /** Redirect to original page */
        $this->response->redirect(Common::url('manage-tags.php', $this->options->adminUrl));
    }

    /**
     * 刷新Label
     *
     * @access public
     * @return void
     * @throws Exception
     */
    public function refreshTag()
    {
        $tags = $this->request->filter('int')->getArray('mid');
        if ($tags) {
            foreach ($tags as $tag) {
                $this->refreshCountByTypeAndStatus($tag, 'post');
            }

            // 自动清理Label
            $this->clearTags();

            Notice::alloc()->set(_t('Tags refreshed.'), 'success');
        } else {
            Notice::alloc()->set(_t('No tag has been selected.'));
        }

        /** Redirect to original page */
        $this->response->goBack();
    }


    /**
     * 清理没有任何内容的Label
     *
     * @throws Exception
     */
    public function clearTags()
    {
        // 取出count为0的Label
        $tags = array_column($this->db->fetchAll($this->select('mid')
            ->where('type = ? AND count = ?', 'tags', 0)), 'mid');

        foreach ($tags as $tag) {
            // 确认是否已经没有关联了
            $content = $this->db->fetchRow($this->db->select('cid')
                ->from('table.relationships')->where('mid = ?', $tag)
                ->limit(1));

            if (empty($content)) {
                $this->db->query($this->db->delete('table.metas')
                    ->where('mid = ?', $tag));
            }
        }
    }

    /**
     * Entry point,绑定事件
     *
     * @access public
     * @return void
     * @throws Exception
     */
    public function action()
    {
        $this->security->protect();
        $this->on($this->request->is('do=insert'))->insertTag();
        $this->on($this->request->is('do=update'))->updateTag();
        $this->on($this->request->is('do=delete'))->deleteTag();
        $this->on($this->request->is('do=merge'))->mergeTag();
        $this->on($this->request->is('do=refresh'))->refreshTag();
        $this->response->redirect($this->options->adminUrl);
    }
}
