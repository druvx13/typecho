<?php

namespace Widget\Metas\Category;

use Typecho\Common;
use Typecho\Db\Exception;
use Typecho\Validate;
use Typecho\Widget\Helper\Form;
use Widget\Base\Metas;
use Widget\ActionInterface;
use Widget\Metas\EditTrait;
use Widget\Notice;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 编辑分类组件
 *
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
     * @throws \Exception
     */
    public function execute()
    {
        /** Editor or higher permission */
        $this->user->pass('editor');
    }

    /**
     * 判断分类是否存在
     *
     * @param integer $mid 分类主键
     * @return boolean
     * @throws Exception
     */
    public function categoryExists(int $mid): bool
    {
        $category = $this->db->fetchRow($this->db->select()
            ->from('table.metas')
            ->where('type = ?', 'category')
            ->where('mid = ?', $mid)->limit(1));

        return isset($category);
    }

    /**
     * 判断分Class name称是否存在
     * fix #1843 将重复性判断限制在同Mon父分类下
     *
     * @param string $name 分Class name称
     * @return boolean
     * @throws Exception
     */
    public function nameExists(string $name): bool
    {
        $select = $this->db->select()
            ->from('table.metas')
            ->where('type = ?', 'category')
            ->where('name = ?', $name)
            ->limit(1);

        if ($this->request->is('mid')) {
            $select->where('mid <> ?', $this->request->get('mid'));
        }

        // 只在同Mon父分类下判断重复性
        $select->where('parent = ?', $this->request->filter('int')->get('parent', 0));

        $category = $this->db->fetchRow($select);
        return !$category;
    }

    /**
     * 判断分Class name转换到缩略名后是否合法
     *
     * @param string $name 分Class name
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
     * 判断分类缩略名是否存在
     *
     * @param string $slug Slug
     * @return boolean
     * @throws Exception
     */
    public function slugExists(string $slug): bool
    {
        $select = $this->db->select()
            ->from('table.metas')
            ->where('type = ?', 'category')
            ->where('slug = ?', Common::slugName($slug))
            ->limit(1);

        if ($this->request->is('mid')) {
            $select->where('mid <> ?', $this->request->get('mid'));
        }

        $category = $this->db->fetchRow($select);
        return !$category;
    }

    /**
     * 增加分类
     *
     * @throws Exception
     */
    public function insertCategory()
    {
        if ($this->form('insert')->validate()) {
            $this->response->goBack();
        }

        /** Fetch data */
        $category = $this->request->from('name', 'slug', 'description', 'parent');

        $category['slug'] = Common::slugName(Common::strBy($category['slug'] ?? null, $category['name']));
        $category['type'] = 'category';
        $category['order'] = $this->getMaxOrder('category', $category['parent']) + 1;

        /** Insert data */
        $category['mid'] = $this->insert($category);
        $this->push($category);

        /** Set highlight */
        Notice::alloc()->highlight($this->theId);

        /** Notice message */
        Notice::alloc()->set(
            _t('Category <a href="%s">%s</a> added.', $this->permalink, $this->name),
            'success'
        );

        /** Redirect to original page */
        $this->response->redirect(Common::url('manage-categories.php'
            . ($category['parent'] ? '?parent=' . $category['parent'] : ''), $this->options->adminUrl));
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
        $form = new Form($this->security->getIndex('/action/metas-category-edit'), Form::POST_METHOD);

        /** Category name */
        $name = new Form\Element\Text('name', null, null, _t('分Class name称') . ' *');
        $form->addInput($name);

        /** Category slug */
        $slug = new Form\Element\Text(
            'slug',
            null,
            null,
            _t('Category abbreviation'),
            _t('Category abbreviations are used to create friendly URL. We recommend that you use alphanumeric, underlines and dashes.')
        );
        $form->addInput($slug);

        /** 父级分类 */
        $options = [0 => _t('Deselect')];
        $parents = Rows::allocWithAlias(
            'options',
            ($this->request->is('mid') ? 'ignore=' . $this->request->get('mid') : '')
        );

        while ($parents->next()) {
            $options[$parents->mid] = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $parents->levels) . $parents->name;
        }

        $parent = new Form\Element\Select(
            'parent',
            $options,
            $this->request->get('parent'),
            _t('Parent category'),
            _t('This category will be archived under the parent category of your choice.')
        );
        $form->addInput($parent);

        /** 分类描述 */
        $description = new Form\Element\Textarea(
            'description',
            null,
            null,
            _t('Category description.'),
            _t('This text is used to describe taxonomies. Certain themes will display this information.')
        );
        $form->addInput($description);

        /** Category action */
        $do = new Form\Element\Hidden('do');
        $form->addInput($do);

        /** Category primary key */
        $mid = new Form\Element\Hidden('mid');
        $form->addInput($mid);

        /** Submit button */
        $submit = new Form\Element\Submit();
        $submit->input->setAttribute('class', 'btn primary');
        $form->addItem($submit);

        if (isset($this->request->mid) && 'insert' != $action) {
            /** Update mode */
            $meta = $this->db->fetchRow($this->select()
                ->where('mid = ?', $this->request->mid)
                ->where('type = ?', 'category')->limit(1));

            if (!$meta) {
                $this->response->redirect(Common::url('manage-categories.php', $this->options->adminUrl));
            }

            $name->value($meta['name']);
            $slug->value($meta['slug']);
            $parent->value($meta['parent']);
            $description->value($meta['description']);
            $do->value('update');
            $mid->value($meta['mid']);
            $submit->value(_t('Edit a category.'));
            $_action = 'update';
        } else {
            $do->value('insert');
            $submit->value(_t('Add a new category.'));
            $_action = 'insert';
        }

        if (empty($action)) {
            $action = $_action;
        }

        /** Add validation rule to form */
        if ('insert' == $action || 'update' == $action) {
            $name->addRule('required', _t('You must enter a name for category.'));
            $name->addRule([$this, 'nameExists'], _t('Category already exists.'));
            $name->addRule([$this, 'nameToSlug'], _t('This category name cannot be converted to an abbreviation.'));
            $name->addRule('xssCheck', _t('Please do not use special characters in category names'));
            $slug->addRule([$this, 'slugExists'], _t('Abbreviation already exists.'));
            $slug->addRule('xssCheck', _t('Please do not include special characters in the thumbnail name.'));
        }

        if ('update' == $action) {
            $mid->addRule('required', _t('Category key does not exist.'));
            $mid->addRule([$this, 'categoryExists'], _t('This category does not exist.'));
        }

        return $form;
    }

    /**
     * Update category
     *
     * @throws Exception
     */
    public function updateCategory()
    {
        if ($this->form('update')->validate()) {
            $this->response->goBack();
        }

        /** Fetch data */
        $category = $this->request->from('name', 'slug', 'description', 'parent');
        $category['mid'] = $this->request->get('mid');
        $category['slug'] = Common::slugName(Common::strBy($category['slug'] ?? null, $category['name']));
        $category['type'] = 'category';
        $current = $this->db->fetchRow($this->select()->where('mid = ?', $category['mid']));

        if ($current['parent'] != $category['parent']) {
            $parent = $this->db->fetchRow($this->select()->where('mid = ?', $category['parent']));

            if ($parent['mid'] == $category['mid']) {
                $category['order'] = $parent['order'];
                $this->update([
                    'parent' => $current['parent'],
                    'order'  => $current['order']
                ], $this->db->sql()->where('mid = ?', $parent['mid']));
            } else {
                $category['order'] = $this->getMaxOrder('category', $category['parent']) + 1;
            }
        }

        /** Update data */
        $this->update($category, $this->db->sql()->where('mid = ?', $this->request->filter('int')->get('mid')));
        $this->push($category);

        /** Set highlight */
        Notice::alloc()->highlight($this->theId);

        /** Notice message */
        Notice::alloc()
            ->set(_t('Category <a href="%s">%s</a> updated.', $this->permalink, $this->name), 'success');

        /** Redirect to original page */
        $this->response->redirect(Common::url('manage-categories.php'
            . ($category['parent'] ? '?parent=' . $category['parent'] : ''), $this->options->adminUrl));
    }

    /**
     * Delete category
     *
     * @access public
     * @return void
     * @throws Exception
     */
    public function deleteCategory()
    {
        $categories = $this->request->filter('int')->getArray('mid');
        $deleteCount = 0;

        foreach ($categories as $category) {
            $parent = $this->db->fetchObject($this->select()->where('mid = ?', $category))->parent;

            if ($this->delete($this->db->sql()->where('mid = ?', $category))) {
                $this->db->query($this->db->delete('table.relationships')->where('mid = ?', $category));
                $this->update(['parent' => $parent], $this->db->sql()->where('parent = ?', $category));
                $deleteCount++;
            }
        }

        /** Notice message */
        Notice::alloc()
            ->set($deleteCount > 0 ? _t('Category deleted.') : _t('No category to delete.'), $deleteCount > 0 ? 'success' : 'notice');

        /** Redirect to original page */
        $this->response->goBack();
    }

    /**
     * 合并分类
     * @throws Exception
     */
    public function mergeCategory()
    {
        /** Validate data */
        $validator = new Validate();
        $validator->addRule('merge', 'required', _t('Category key does not exist.'));
        $validator->addRule('merge', [$this, 'categoryExists'], _t('Please choose categories to combine.'));

        if ($error = $validator->run($this->request->from('merge'))) {
            Notice::alloc()->set($error, 'error');
            $this->response->goBack();
        }

        $merge = $this->request->get('merge');
        $categories = $this->request->filter('int')->getArray('mid');

        if ($categories) {
            $this->merge($merge, 'category', $categories);

            /** Notice message */
            Notice::alloc()->set(_t('Categories combined.'), 'success');
        } else {
            Notice::alloc()->set(_t('No category selected.'));
        }

        /** Redirect to original page */
        $this->response->goBack();
    }

    /**
     * 分类Sort
     * @throws Exception
     */
    public function sortCategory()
    {
        $categories = $this->request->filter('int')->getArray('mid');
        if ($categories) {
            $this->sort($categories, 'category');
        }

        if (!$this->request->isAjax()) {
            /** Redirect to original page */
            $this->response->redirect(Common::url('manage-categories.php', $this->options->adminUrl));
        } else {
            $this->response->throwJson(['success' => 1, 'message' => _t('Categories sorted.')]);
        }
    }

    /**
     * 刷新分类
     *
     * @throws Exception
     */
    public function refreshCategory()
    {
        $categories = $this->request->filter('int')->getArray('mid');
        if ($categories) {
            foreach ($categories as $category) {
                $this->refreshCountByTypeAndStatus($category, 'post');
            }

            Notice::alloc()->set(_t('Categories refreshed.'), 'success');
        } else {
            Notice::alloc()->set(_t('No category selected.'));
        }

        /** Redirect to original page */
        $this->response->goBack();
    }

    /**
     * 设置默认分类
     *
     * @throws Exception
     */
    public function defaultCategory()
    {
        /** Validate data */
        $validator = new Validate();
        $validator->addRule('mid', 'required', _t('Category key does not exist.'));
        $validator->addRule('mid', [$this, 'categoryExists'], _t('This category does not exist.'));

        if ($error = $validator->run($this->request->from('mid'))) {
            Notice::alloc()->set($error, 'error');
        } else {
            $this->db->query($this->db->update('table.options')
                ->rows(['value' => $this->request->get('mid')])
                ->where('name = ?', 'defaultCategory'));

            $this->db->fetchRow($this->select()->where('mid = ?', $this->request->get('mid'))
                ->where('type = ?', 'category')->limit(1), [$this, 'push']);

            /** Set highlight */
            Notice::alloc()->highlight($this->theId);

            /** Notice message */
            Notice::alloc()->set(
                _t('<a href="%s">%s</a> has been set to the default category.', $this->permalink, $this->name),
                'success'
            );
        }

        /** Redirect to original page */
        $this->response->redirect(Common::url('manage-categories.php', $this->options->adminUrl));
    }

    /**
     * Get menu title
     *
     * @return string|null
     * @throws \Typecho\Widget\Exception|Exception
     */
    public function getMenuTitle(): ?string
    {
        if ($this->request->is('mid')) {
            $category = $this->db->fetchRow($this->select()
                ->where('type = ? AND mid = ?', 'category', $this->request->filter('int')->get('mid')));

            if (!empty($category)) {
                return _t('Edit Category %s', $category['name']);
            }
        }

        if ($this->request->is('parent')) {
            $category = $this->db->fetchRow($this->select()
                ->where('type = ? AND mid = ?', 'category', $this->request->filter('int')->get('parent')));

            if (!empty($category)) {
                return _t('New subcategory for %s', $category['name']);
            }
        } else {
            return null;
        }

        throw new \Typecho\Widget\Exception(_t('This category does not exist.'), 404);
    }

    /**
     * Entry point
     *
     * @access public
     * @return void
     * @throws Exception
     */
    public function action()
    {
        $this->security->protect();
        $this->on($this->request->is('do=insert'))->insertCategory();
        $this->on($this->request->is('do=update'))->updateCategory();
        $this->on($this->request->is('do=delete'))->deleteCategory();
        $this->on($this->request->is('do=merge'))->mergeCategory();
        $this->on($this->request->is('do=sort'))->sortCategory();
        $this->on($this->request->is('do=refresh'))->refreshCategory();
        $this->on($this->request->is('do=default'))->defaultCategory();
        $this->response->redirect($this->options->adminUrl);
    }
}
