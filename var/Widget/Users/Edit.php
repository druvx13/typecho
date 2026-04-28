<?php

namespace Widget\Users;

use Typecho\Common;
use Typecho\Widget\Exception;
use Typecho\Widget\Helper\Form;
use Utils\PasswordHash;
use Widget\ActionInterface;
use Widget\Base\Users;
use Widget\Notice;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * Edit user widget
 *
 * @link typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Edit extends Users implements ActionInterface
{
    use EditTrait;

    /**
     * Execute action
     *
     * @return void
     * @throws Exception|\Typecho\Db\Exception
     */
    public function execute()
    {
        /** 管理员以上权限 */
        $this->user->pass('administrator');

        /** Update mode */
        if (($this->request->is('uid') && 'delete' != $this->request->get('do')) || $this->request->is('do=update')) {
            $this->db->fetchRow($this->select()
                ->where('uid = ?', $this->request->get('uid'))->limit(1), [$this, 'push']);

            if (!$this->have()) {
                throw new Exception(_t('This user does not exist.'), 404);
            }
        }
    }

    /**
     * Get menu title
     *
     * @return string
     */
    public function getMenuTitle(): string
    {
        return _t('Edit %s', $this->name);
    }

    /**
     * 判断用户是否存在
     *
     * @param integer $uid 用户主键
     * @return boolean
     * @throws \Typecho\Db\Exception
     */
    public function userExists(int $uid): bool
    {
        $user = $this->db->fetchRow($this->db->select()
            ->from('table.users')
            ->where('uid = ?', $uid)->limit(1));

        return !empty($user);
    }

    /**
     * 增加用户
     *
     * @throws \Typecho\Db\Exception
     */
    public function insertUser()
    {
        if ($this->form('insert')->validate()) {
            $this->response->goBack();
        }

        $hasher = new PasswordHash(8, true);

        /** Fetch data */
        $user = $this->request->from('name', 'mail', 'screenName', 'password', 'url', 'group');
        $user['screenName'] = empty($user['screenName']) ? $user['name'] : $user['screenName'];
        $user['password'] = $hasher->hashPassword($user['password']);
        $user['created'] = $this->options->time;

        /** Insert data */
        $user['uid'] = $this->insert($user);

        /** Set highlight */
        Notice::alloc()->highlight('user-' . $user['uid']);

        /** Notice message */
        Notice::alloc()->set(_t('User %s added.', $user['screenName']), 'success');

        /** Redirect to original page */
        $this->response->redirect(Common::url('manage-users.php', $this->options->adminUrl));
    }

    /**
     * Generate form
     *
     * @access public
     * @param string|null $action Form action
     * @return Form
     */
    public function form(?string $action = null): Form
    {
        /** Build form */
        $form = new Form($this->security->getIndex('/action/users-edit'), Form::POST_METHOD);

        /** 用户名称 */
        $name = new Form\Element\Text('name', null, null, _t('username') . ' *', _t('This username will be used in login.')
            . '<br />' . _t('Please do not use an existent username.'));
        $form->addInput($name);

        /** Email address */
        $mail = new Form\Element\Text('mail', null, null, _t('email address') . ' *', _t('Email address will be used for contact.')
            . '<br />' . _t('Please do not use an an existent email address.'));
        $form->addInput($mail);

        /** User screen name */
        $screenName = new Form\Element\Text('screenName', null, null, _t('Nickname'), _t('Nickname can be different from username. It will be shown on website front end.')
            . '<br />' . _t('If you leave this blank, typecho will use your username by default.'));
        $form->addInput($screenName);

        /** User password */
        $password = new Form\Element\Password('password', null, null, _t('User password.'), _t('Give this user a password.')
            . '<br />' . _t('For security reasons, we recommend you use a password combining special characters and alphanumeric.'));
        $password->input->setAttribute('class', 'w-60');
        $form->addInput($password);

        /** User password confirmation */
        $confirm = new Form\Element\Password('confirm', null, null, _t('Confirm your password.'), _t('Please confirm your password. It should be the same as the one you typed above.'));
        $confirm->input->setAttribute('class', 'w-60');
        $form->addInput($confirm);

        /** Personal homepage URL */
        $url = new Form\Element\Text('url', null, null, _t('Homepage'), _t('Personal homepage URL for this user. Must start with <code>https://</code>.'));
        $form->addInput($url);

        /** 用户组 */
        $group = new Form\Element\Select(
            'group',
            [
                'subscriber'  => _t('Followers'),
                'contributor' => _t('Contributors'), 'editor' => _t('Editors'), 'administrator' => _t('Admin')
            ],
            null,
            _t('User groups'),
            _t('Different user groups have different permissions.') . '<br />' . _t('See the <a href="https://docs.typecho.org/develop/acl">permission reference</a> for details.')
        );
        $form->addInput($group);

        /** User action */
        $do = new Form\Element\Hidden('do');
        $form->addInput($do);

        /** 用户主键 */
        $uid = new Form\Element\Hidden('uid');
        $form->addInput($uid);

        /** Submit button */
        $submit = new Form\Element\Submit();
        $submit->input->setAttribute('class', 'btn primary');
        $form->addItem($submit);

        if ($this->request->is('uid')) {
            $submit->value(_t('Edit a user'));
            $name->value($this->name);
            $screenName->value($this->screenName);
            $url->value($this->url);
            $mail->value($this->mail);
            $group->value($this->group);
            $do->value('update');
            $uid->value($this->uid);
            $_action = 'update';
        } else {
            $submit->value(_t('Add a user'));
            $do->value('insert');
            $_action = 'insert';
        }

        if (empty($action)) {
            $action = $_action;
        }

        /** Add validation rule to form */
        if ('insert' == $action || 'update' == $action) {
            $screenName->addRule([$this, 'screenNameExists'], _t('Nickname already exists.'));
            $screenName->addRule('xssCheck', _t('Please do not use special characters in usernames'));
            $url->addRule('url', _t('Invalid omepage URL format'));
            $mail->addRule('required', _t('You must enter an email address.'));
            $mail->addRule([$this, 'mailExists'], _t('Email address already exists.'));
            $mail->addRule('email', _t('Invalid Email address'));
            $password->addRule('minLength', _t('For the security of your account, please choose a password containing at least 6 characters.'), 6);
            $confirm->addRule('confirm', _t('Passwords do not match.'), 'password');
        }

        if ('insert' == $action) {
            $name->addRule('required', _t('You must enter a username.'));
            $name->addRule('xssCheck', _t('Please do not include special characters in username.'));
            $name->addRule([$this, 'nameExists'], _t('Username already exist.'));
            $password->label(_t('User password.') . ' *');
            $confirm->label(_t('Confirm your password.') . ' *');
            $password->addRule('required', _t('You must enter a password.'));
        }

        if ('update' == $action) {
            $name->input->setAttribute('disabled', 'disabled');
            $uid->addRule('required', _t('User key does not exist.'));
            $uid->addRule([$this, 'userExists'], _t('This user does not exist.'));
        }

        return $form;
    }

    /**
     * Update user
     *
     * @throws \Typecho\Db\Exception
     */
    public function updateUser()
    {
        if ($this->form('update')->validate()) {
            $this->response->goBack();
        }

        /** Fetch data */
        $user = $this->request->from('mail', 'screenName', 'password', 'url', 'group');
        $user['screenName'] = empty($user['screenName']) ? $user['name'] : $user['screenName'];
        if (empty($user['password'])) {
            unset($user['password']);
        } else {
            $hasher = new PasswordHash(8, true);
            $user['password'] = $hasher->hashPassword($user['password']);
        }

        /** Update data */
        $this->update($user, $this->db->sql()->where('uid = ?', $this->request->get('uid')));

        /** Set highlight */
        Notice::alloc()->highlight('user-' . $this->request->get('uid'));

        /** Notice message */
        Notice::alloc()->set(_t('User %s updated.', $user['screenName']), 'success');

        /** Redirect to original page */
        $this->response->redirect(Common::url('manage-users.php?' .
            $this->getPageOffsetQuery($this->request->get('uid')), $this->options->adminUrl));
    }

    /**
     * Get page offset URL query
     *
     * @param integer $uid 用户id
     * @return string
     * @throws \Typecho\Db\Exception
     */
    protected function getPageOffsetQuery(int $uid): string
    {
        return 'page=' . $this->getPageOffset('uid', $uid);
    }

    /**
     * Delete user
     *
     * @throws \Typecho\Db\Exception
     */
    public function deleteUser()
    {
        $users = $this->request->filter('int')->getArray('uid');
        $masterUserId = $this->db->fetchObject($this->db->select(['MIN(uid)' => 'num'])->from('table.users'))->num;
        $deleteCount = 0;

        foreach ($users as $user) {
            if ($masterUserId == $user || $user == $this->user->uid) {
                continue;
            }

            if ($this->delete($this->db->sql()->where('uid = ?', $user))) {
                $deleteCount++;
            }
        }

        /** Notice message */
        Notice::alloc()->set(
            $deleteCount > 0 ? _t('User deleted.') : _t('No user to delete.'),
            $deleteCount > 0 ? 'success' : 'notice'
        );

        /** Redirect to original page */
        $this->response->redirect(Common::url('manage-users.php', $this->options->adminUrl));
    }

    /**
     * Entry point
     *
     * @access public
     * @return void
     */
    public function action()
    {
        $this->user->pass('administrator');
        $this->security->protect();
        $this->on($this->request->is('do=insert'))->insertUser();
        $this->on($this->request->is('do=update'))->updateUser();
        $this->on($this->request->is('do=delete'))->deleteUser();
        $this->response->redirect($this->options->adminUrl);
    }
}
