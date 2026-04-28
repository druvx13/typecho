<?php

namespace Widget\Contents\Attachment;

use Typecho\Common;
use Typecho\Widget\Exception;
use Typecho\Widget\Helper\Form;
use Typecho\Widget\Helper\Layout;
use Widget\ActionInterface;
use Widget\Base\Contents;
use Widget\Contents\PrepareEditTrait;
use Widget\Notice;
use Widget\Upload;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * Post editing widget
 *
 * @author qining
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Edit extends Contents implements ActionInterface
{
    use PrepareEditTrait;

    /**
     * Execute action
     *
     * @throws Exception|\Typecho\Db\Exception
     */
    public function execute()
    {
        /** Must be contributor or higher */
        $this->user->pass('contributor');
    }

    /**
     * 判断File name转换到缩略名后是否合法
     *
     * @param string $name File name
     * @return boolean
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
     * 判断文件缩略名是否存在
     *
     * @param string $slug Slug
     * @return boolean
     * @throws \Typecho\Db\Exception
     */
    public function slugExists(string $slug): bool
    {
        $select = $this->db->select()
            ->from('table.contents')
            ->where('type = ?', 'attachment')
            ->where('slug = ?', Common::slugName($slug))
            ->limit(1);

        if ($this->request->is('cid')) {
            $select->where('cid <> ?', $this->request->get('cid'));
        }

        $attachment = $this->db->fetchRow($select);
        return !$attachment;
    }

    /**
     * 更新文件
     *
     * @throws \Typecho\Db\Exception
     * @throws Exception
     */
    public function updateAttachment()
    {
        if ($this->form()->validate()) {
            $this->response->goBack();
        }

        /** Fetch data */
        $input = $this->request->from('name', 'slug', 'description');
        $input['slug'] = Common::slugName(Common::strBy($input['slug'] ?? null, $input['name']));

        $attachment['title'] = $input['name'];
        $attachment['slug'] = $input['slug'];

        $content = $this->attachment->toArray();
        $content['description'] = $input['description'];

        $attachment['text'] = json_encode($content);
        $cid = $this->request->filter('int')->get('cid');

        /** Update data */
        $updateRows = $this->update($attachment, $this->db->sql()->where('cid = ?', $cid));

        if ($updateRows > 0) {
            $this->db->fetchRow($this->select()
                ->where('table.contents.type = ?', 'attachment')
                ->where('table.contents.cid = ?', $cid)
                ->limit(1), [$this, 'push']);

            /** Set highlight */
            Notice::alloc()->highlight($this->theId);

            /** Notice message */
            Notice::alloc()->set('publish' == $this->status ?
                _t('File <a href="%s">%s</a> updated.', $this->permalink, $this->title) :
                _t('Unarchived file %s updated.', $this->title), 'success');
        }

        /** Redirect to original page */
        $this->response->redirect(Common::url('manage-medias.php?' .
            $this->getPageOffsetQuery($cid, $this->status), $this->options->adminUrl));
    }

    /**
     * Generate form
     *
     * @return Form
     */
    public function form(): Form
    {
        /** Build form */
        $form = new Form($this->security->getIndex('/action/contents-attachment-edit'), Form::POST_METHOD);

        /** File name */
        $name = new Form\Element\Text('name', null, $this->title, _t('Title') . ' *');
        $form->addInput($name);

        /** 文件缩略名 */
        $slug = new Form\Element\Text(
            'slug',
            null,
            $this->slug,
            _t('Abbreviation'),
            _t('File abbreviations are used to create friendly URL. We recommend that you use alphanumeric, underlines and dashes.')
        );
        $form->addInput($slug);

        /** 文件描述 */
        $description = new Form\Element\Textarea(
            'description',
            null,
            $this->attachment->description,
            _t('Description'),
            _t('This text is used to describe files. It will be displayed in certain themes.')
        );
        $form->addInput($description);

        /** Category action */
        $do = new Form\Element\Hidden('do', null, 'update');
        $form->addInput($do);

        /** Category primary key */
        $cid = new Form\Element\Hidden('cid', null, $this->cid);
        $form->addInput($cid);

        /** Submit button */
        $submit = new Form\Element\Submit(null, null, _t('Submit edit.'));
        $submit->input->setAttribute('class', 'btn primary');
        $delete = new Layout('a', [
            'href'  => $this->security->getIndex('/action/contents-attachment-edit?do=delete&cid=' . $this->cid),
            'class' => 'operate-delete',
            'lang'  => _t('Delete file %s?', $this->attachment->name)
        ]);
        $submit->container($delete->html(_t('Delete file.')));
        $form->addItem($submit);

        $name->addRule('required', _t('You must enter a name for file.'));
        $name->addRule([$this, 'nameToSlug'], _t('The file name cannot be converted to an abbreviation.'));
        $slug->addRule([$this, 'slugExists'], _t('Abbreviation already exists.'));

        return $form;
    }

    /**
     * Get page offset URL query
     *
     * @param integer $cid 文件id
     * @param string|null $status Status
     * @return string
     * @throws \Typecho\Db\Exception|Exception
     */
    protected function getPageOffsetQuery(int $cid, string $status = null): string
    {
        return 'page=' . $this->getPageOffset(
            'cid',
            $cid,
            'attachment',
            $status,
            $this->user->pass('editor', true) ? 0 : $this->user->uid
        );
    }

    /**
     * Delete post
     *
     * @throws \Typecho\Db\Exception
     */
    public function deleteAttachment()
    {
        $posts = $this->request->filter('int')->getArray('cid');
        $deleteCount = 0;

        $this->deleteByIds($posts, $deleteCount);

        if ($this->request->isAjax()) {
            $this->response->throwJson($deleteCount > 0 ? ['code' => 200, 'message' => _t('File deleted.')]
                : ['code' => 500, 'message' => _t('No file to be deleted.')]);
        } else {
            /** Set notice message */
            Notice::alloc()
                ->set(
                    $deleteCount > 0 ? _t('File deleted.') : _t('No file to be deleted.'),
                    $deleteCount > 0 ? 'success' : 'notice'
                );

            /** Return to original page */
            $this->response->redirect(Common::url('manage-medias.php', $this->options->adminUrl));
        }
    }

    /**
     * clearAttachment
     *
     * @access public
     * @return void
     * @throws \Typecho\Db\Exception
     */
    public function clearAttachment()
    {
        $page = 1;
        $deleteCount = 0;

        do {
            $posts = array_column($this->db->fetchAll($this->db->select('cid')
                ->from('table.contents')
                ->where('type = ? AND parent = ?', 'attachment', 0)
                ->page($page, 100)), 'cid');
            $page++;

            $this->deleteByIds($posts, $deleteCount);
        } while (count($posts) == 100);

        /** Set notice message */
        Notice::alloc()->set(
            $deleteCount > 0 ? _t('Successfully cleaned files that have not been archived.') : _t('No archived files cleaned'),
            $deleteCount > 0 ? 'success' : 'notice'
        );

        /** Return to original page */
        $this->response->redirect(Common::url('manage-medias.php', $this->options->adminUrl));
    }

    /**
     * @return $this
     * @throws Exception
     * @throws \Typecho\Db\Exception
     */
    public function prepare(): self
    {
        return $this->prepareEdit('attachment', false, _t('File does not exist.'));
    }

    /**
     * Bind action
     *
     * @access public
     * @return void
     */
    public function action()
    {
        $this->security->protect();
        $this->on($this->request->is('do=delete'))->deleteAttachment();
        $this->on($this->request->is('do=update'))
            ->prepare()->updateAttachment();
        $this->on($this->request->is('do=clear'))->clearAttachment();
        $this->response->redirect($this->options->adminUrl);
    }

    /**
     * @param array $posts
     * @param int $deleteCount
     * @return void
     */
    protected function deleteByIds(array $posts, int &$deleteCount): void
    {
        foreach ($posts as $post) {
            // Remove plugin interface
            self::pluginHandle()->call('delete', $post, $this);

            $condition = $this->db->sql()->where('cid = ?', $post);
            $row = $this->db->fetchRow($this->select()
                ->where('table.contents.type = ?', 'attachment')
                ->where('table.contents.cid = ?', $post)
                ->limit(1), [$this, 'push']);

            if ($this->isWriteable(clone $condition) && $this->delete($condition)) {
                /** Delete file */
                Upload::deleteHandle($this->toColumn(['cid', 'attachment', 'parent']));

                /** Delete comment */
                $this->db->query($this->db->delete('table.comments')
                    ->where('cid = ?', $post));

                // Complete remove plugin interface
                self::pluginHandle()->call('finishDelete', $post, $this);

                $deleteCount++;
            }

            unset($condition);
        }
    }
}
