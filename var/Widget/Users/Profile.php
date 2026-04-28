<?php

namespace Widget\Users;

use Typecho\Common;
use Typecho\Db\Exception;
use Typecho\Plugin;
use Typecho\Widget\Helper\Form;
use Utils\PasswordHash;
use Widget\ActionInterface;
use Widget\Base\Options;
use Widget\Base\Users;
use Widget\Notice;
use Widget\Plugins\Rows;

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
class Profile extends Users implements ActionInterface
{
    use EditTrait;

    /**
     * Execute action
     */
    public function execute()
    {
        /** 注册用户以上权限 */
        $this->user->pass('subscriber');
        $this->request->setParam('uid', $this->user->uid);
    }

    /**
     * Output form structure
     *
     * @access public
     * @return Form
     */
    public function optionsForm(): Form
    {
        /** Build form */
        $form = new Form($this->security->getIndex('/action/users-profile'), Form::POST_METHOD);

        /** 撰写设置 */
        $markdown = new Form\Element\Radio(
            'markdown',
            ['0' => _t('Close.'), '1' => _t('Open')],
            $this->options->markdown,
            _t('Edit and parse content in Markdown syntax'),
            _t('Using <a href="https://daringfireball.net/projects/markdown/">Markdown</a> syntax makes writing simpler and more intuitive.')
            . '<br />' . _t('Enabling this function will not affect contents previously edited without Markdown syntax.')
        );
        $form->addInput($markdown);

        $xmlrpcMarkdown = new Form\Element\Radio(
            'xmlrpcMarkdown',
            ['0' => _t('Close.'), '1' => _t('Open')],
            $this->options->xmlrpcMarkdown,
            _t('Use Markdown syntax in the XMLRPC interface'),
            _t('For offline editors that fully support <a href="https://daringfireball.net/projects/markdown/">Markdown</a>, enabling this option prevents content from being converted to HTML.')
        );
        $form->addInput($xmlrpcMarkdown);

        /** 自动保存 */
        $autoSave = new Form\Element\Radio(
            'autoSave',
            ['0' => _t('Close.'), '1' => _t('Open')],
            $this->options->autoSave,
            _t('Auto save.'),
            _t('Auto saving can protect your post from accidents.')
        );
        $form->addInput($autoSave);

        /** 默认允许 */
        $allow = [];
        if ($this->options->defaultAllowComment) {
            $allow[] = 'comment';
        }

        if ($this->options->defaultAllowPing) {
            $allow[] = 'ping';
        }

        if ($this->options->defaultAllowFeed) {
            $allow[] = 'feed';
        }

        $defaultAllow = new Form\Element\Checkbox(
            'defaultAllow',
            ['comment' => _t('Commentable'), 'ping' => _t('Citable'), 'feed' => _t('Shown in aggregation.')],
            $allow,
            _t('Allow by default.'),
            _t('Set default allowed permissions you use regularly.')
        );
        $form->addInput($defaultAllow);

        /** User action */
        $do = new Form\Element\Hidden('do', null, 'options');
        $form->addInput($do);

        /** Submit button */
        $submit = new Form\Element\Submit('submit', null, _t('Save settings.'));
        $submit->input->setAttribute('class', 'btn primary');
        $form->addItem($submit);

        return $form;
    }

    /**
     * 自定义设置列表
     *
     * @throws Plugin\Exception
     */
    public function personalFormList()
    {
        $plugins = Rows::alloc('activated=1');

        while ($plugins->next()) {
            if ($plugins->personalConfig) {
                [$pluginFileName, $className] = Plugin::portal($plugins->name, $this->options->pluginDir);

                $form = $this->personalForm($plugins->name, $className, $pluginFileName, $group);
                if ($this->user->pass($group, true)) {
                    echo '<br><section id="personal-' . $plugins->name . '">';
                    echo '<h3>' . $plugins->title . '</h3>';

                    $form->render();

                    echo '</section>';
                }
            }
        }
    }

    /**
     * 输出自定义设置选项
     *
     * @access public
     * @param string $pluginName Plugin name
     * @param string $className Class name称
     * @param string $pluginFileName 插件File name
     * @param string|null $group User group
     * @throws Plugin\Exception
     */
    public function personalForm(string $pluginName, string $className, string $pluginFileName, ?string &$group): Form
    {
        /** Build form */
        $form = new Form($this->security->getIndex('/action/users-profile'), Form::POST_METHOD);
        $form->setAttribute('name', $pluginName);
        $form->setAttribute('id', $pluginName);

        require_once $pluginFileName;
        $group = call_user_func([$className, 'personalConfig'], $form);
        $group = $group ?: 'subscriber';

        $options = $this->options->personalPlugin($pluginName);

        if (!empty($options)) {
            foreach ($options as $key => $val) {
                $form->getInput($key)->value($val);
            }
        }

        $form->addItem(new Form\Element\Hidden('do', null, 'personal'));
        $form->addItem(new Form\Element\Hidden('plugin', null, $pluginName));
        $submit = new Form\Element\Submit('submit', null, _t('Save settings.'));
        $submit->input->setAttribute('class', 'btn primary');
        $form->addItem($submit);
        return $form;
    }

    /**
     * Update user
     *
     * @throws Exception
     */
    public function updateProfile()
    {
        if ($this->profileForm()->validate()) {
            $this->response->goBack();
        }

        /** Fetch data */
        $user = $this->request->from('mail', 'screenName', 'url');
        $user['screenName'] = empty($user['screenName']) ? $user['name'] : $user['screenName'];

        /** Update data */
        $this->update($user, $this->db->sql()->where('uid = ?', $this->user->uid));

        /** Set highlight */
        Notice::alloc()->highlight('user-' . $this->user->uid);

        /** Notice message */
        Notice::alloc()->set(_t('Your profile has been updated.'), 'success');

        /** Redirect to original page */
        $this->response->goBack();
    }

    /**
     * Generate form
     *
     * @return Form
     */
    public function profileForm(): Form
    {
        /** Build form */
        $form = new Form($this->security->getIndex('/action/users-profile'), Form::POST_METHOD);

        /** User screen name */
        $screenName = new Form\Element\Text('screenName', null, null, _t('Nickname'), _t('Nickname can be different from username. It will be shown on website front end.')
            . '<br />' . _t('If you leave this blank, typecho will use your username by default.'));
        $form->addInput($screenName);

        /** Personal homepage URL */
        $url = new Form\Element\Url('url', null, null, _t('Homepage'), _t('Personal homepage URL for this user. Must start with <code>https://</code>.'));
        $form->addInput($url);

        /** Email address */
        $mail = new Form\Element\Text('mail', null, null, _t('email address') . ' *', _t('Email address will be used for contact.')
            . '<br />' . _t('Please do not use an an existent email address.'));
        $form->addInput($mail);

        /** User action */
        $do = new Form\Element\Hidden('do', null, 'profile');
        $form->addInput($do);

        /** Submit button */
        $submit = new Form\Element\Submit('submit', null, _t('Update my profile.'));
        $submit->input->setAttribute('class', 'btn primary');
        $form->addItem($submit);

        $screenName->value($this->user->screenName);
        $url->value($this->user->url);
        $mail->value($this->user->mail);

        /** Add validation rule to form */
        $screenName->addRule([$this, 'screenNameExists'], _t('Nickname already exists.'));
        $screenName->addRule('xssCheck', _t('Please do not use special characters in usernames'));
        $url->addRule('url', _t('Invalid omepage URL format'));
        $mail->addRule('required', _t('You must enter an email address.'));
        $mail->addRule([$this, 'mailExists'], _t('Email address already exists.'));
        $mail->addRule('email', _t('Invalid Email address'));

        return $form;
    }

    /**
     * Execute update action
     *
     * @throws Exception
     */
    public function updateOptions()
    {
        $settings['autoSave'] = $this->request->is('autoSave=1') ? 1 : 0;
        $settings['markdown'] = $this->request->is('markdown=1') ? 1 : 0;
        $settings['xmlrpcMarkdown'] = $this->request->is('xmlrpcMarkdown=1') ? 1 : 0;
        $defaultAllow = $this->request->getArray('defaultAllow');

        $settings['defaultAllowComment'] = in_array('comment', $defaultAllow) ? 1 : 0;
        $settings['defaultAllowPing'] = in_array('ping', $defaultAllow) ? 1 : 0;
        $settings['defaultAllowFeed'] = in_array('feed', $defaultAllow) ? 1 : 0;

        foreach ($settings as $name => $value) {
            if (
                $this->db->fetchObject($this->db->select(['COUNT(*)' => 'num'])
                    ->from('table.options')->where('name = ? AND user = ?', $name, $this->user->uid))->num > 0
            ) {
                Options::alloc()
                    ->update(
                        ['value' => $value],
                        $this->db->sql()->where('name = ? AND user = ?', $name, $this->user->uid)
                    );
            } else {
                Options::alloc()->insert([
                    'name'  => $name,
                    'value' => $value,
                    'user'  => $this->user->uid
                ]);
            }
        }

        Notice::alloc()->set(_t("Your settings have been saved."), 'success');
        $this->response->goBack();
    }

    /**
     * 更新密码
     *
     * @throws Exception
     */
    public function updatePassword()
    {
        /** Validate form */
        if ($this->passwordForm()->validate()) {
            $this->response->goBack();
        }

        $hasher = new PasswordHash(8, true);
        $password = $hasher->hashPassword($this->request->password);

        /** Update data */
        $this->update(
            ['password' => $password],
            $this->db->sql()->where('uid = ?', $this->user->uid)
        );

        /** Set highlight */
        Notice::alloc()->highlight('user-' . $this->user->uid);

        /** Notice message */
        Notice::alloc()->set(_t('Password has been edited successfully.'), 'success');

        /** Redirect to original page */
        $this->response->goBack();
    }

    /**
     * Generate form
     *
     * @return Form
     */
    public function passwordForm(): Form
    {
        /** Build form */
        $form = new Form($this->security->getIndex('/action/users-profile'), Form::POST_METHOD);

        /** User password */
        $password = new Form\Element\Password('password', null, null, _t('User password.'), _t('Give this user a password.')
            . '<br />' . _t('For security reasons, we recommend you use a password combining special characters and alphanumeric.'));
        $password->input->setAttribute('class', 'w-60');
        $form->addInput($password);

        /** User password confirmation */
        $confirm = new Form\Element\Password('confirm', null, null, _t('Confirm your password.'), _t('Please confirm your password. It should be the same as the one you typed above.'));
        $confirm->input->setAttribute('class', 'w-60');
        $form->addInput($confirm);

        /** User action */
        $do = new Form\Element\Hidden('do', null, 'password');
        $form->addInput($do);

        /** Submit button */
        $submit = new Form\Element\Submit('submit', null, _t('Update your password.'));
        $submit->input->setAttribute('class', 'btn primary');
        $form->addItem($submit);

        $password->addRule('required', _t('You must enter a password.'));
        $password->addRule('minLength', _t('For the security of your account, please choose a password containing at least 6 characters.'), 6);
        $confirm->addRule('confirm', _t('Passwords do not match.'), 'password');

        return $form;
    }

    /**
     * 更新个人设置
     *
     * @throws \Typecho\Widget\Exception
     */
    public function updatePersonal()
    {
        /** Get plugin name */
        $pluginName = $this->request->get('plugin');

        /** Get enabled plugins */
        $plugins = Plugin::export();
        $activatedPlugins = $plugins['activated'];

        /** Get plugin entry point */
        [$pluginFileName, $className] = Plugin::portal(
            $pluginName,
            __TYPECHO_ROOT_DIR__ . '/' . __TYPECHO_PLUGIN_DIR__
        );
        $info = Plugin::parseInfo($pluginFileName);

        if (!$info['personalConfig'] || !isset($activatedPlugins[$pluginName])) {
            throw new \Typecho\Widget\Exception(_t('Cannot configure plugin.'), 500);
        }

        $form = $this->personalForm($pluginName, $className, $pluginFileName, $group);
        $this->user->pass($group);

        /** Validate form */
        if ($form->validate()) {
            $this->response->goBack();
        }

        $settings = $form->getAllRequest();
        unset($settings['do'], $settings['plugin']);
        $name = '_plugin:' . $pluginName;

        if (!$this->personalConfigHandle($className, $settings)) {
            if (
                $this->db->fetchObject($this->db->select(['COUNT(*)' => 'num'])
                    ->from('table.options')->where('name = ? AND user = ?', $name, $this->user->uid))->num > 0
            ) {
                Options::alloc()
                    ->update(
                        ['value' => json_encode($settings)],
                        $this->db->sql()->where('name = ? AND user = ?', $name, $this->user->uid)
                    );
            } else {
                Options::alloc()->insert([
                    'name'  => $name,
                    'value' => json_encode($settings),
                    'user'  => $this->user->uid
                ]);
            }
        }

        /** Notice message */
        Notice::alloc()->set(_t("Settings of %s  saved.", $info['title']), 'success');

        /** Redirect to original page */
        $this->response->redirect(Common::url('profile.php', $this->options->adminUrl));
    }

    /**
     * Process custom configuration value with own function
     *
     * @access public
     * @param string $className Class name
     * @param array $settings Configuration values
     * @return boolean
     */
    public function personalConfigHandle(string $className, array $settings): bool
    {
        if (method_exists($className, 'personalConfigHandle')) {
            call_user_func([$className, 'personalConfigHandle'], $settings, false);
            return true;
        }

        return false;
    }

    /**
     * Entry point
     *
     * @access public
     * @return void
     */
    public function action()
    {
        $this->security->protect();
        $this->on($this->request->is('do=profile'))->updateProfile();
        $this->on($this->request->is('do=options'))->updateOptions();
        $this->on($this->request->is('do=password'))->updatePassword();
        $this->on($this->request->is('do=personal&plugin'))->updatePersonal();
        $this->response->redirect($this->options->siteUrl);
    }
}
