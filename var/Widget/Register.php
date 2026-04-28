<?php

namespace Widget;

use Typecho\Common;
use Typecho\Cookie;
use Typecho\Db\Exception;
use Typecho\Validate;
use Utils\PasswordHash;
use Widget\Base\Users;
use Widget\Users\EditTrait;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * Registration widget
 *
 * @author qining
 * @category typecho
 * @package Widget
 */
class Register extends Users implements ActionInterface
{
    use EditTrait;

    /**
     * Initialization function
     *
     * @throws Exception
     */
    public function action()
    {
        // protect
        $this->security->protect();

        /** If already logged in */
        if ($this->user->hasLogin() || !$this->options->allowRegister) {
            /** Return directly */
            $this->response->redirect($this->options->index);
        }

        /** Initialize validation class */
        $validator = new Validate();
        $validator->addRule('name', 'required', _t('You must enter a username.'));
        $validator->addRule('name', 'minLength', _t('Username should contain at least 2 characters.'), 2);
        $validator->addRule('name', 'maxLength', _t('Username should contain at most 32 characters.'), 32);
        $validator->addRule('name', 'xssCheck', _t('Please do not include special characters in username.'));
        $validator->addRule('name', [$this, 'nameExists'], _t('Username already exist.'));
        $validator->addRule('mail', 'required', _t('You must enter an email address.'));
        $validator->addRule('mail', [$this, 'mailExists'], _t('Email address already exists.'));
        $validator->addRule('mail', 'email', _t('Invalid Email address'));
        $validator->addRule('mail', 'maxLength', _t('Email address should contain at most 64 characters'), 64);

        /** 如果请求中有password */
        if (array_key_exists('password', $_REQUEST)) {
            $validator->addRule('password', 'required', _t('You must enter a password.'));
            $validator->addRule('password', 'minLength', _t('For the security of your account, please choose a password containing at least 6 characters.'), 6);
            $validator->addRule('password', 'maxLength', _t('For the convenience of memory, please choose a password containing at most 18 characters.'), 18);
            $validator->addRule('confirm', 'confirm', _t('Passwords do not match.'), 'password');
        }

        /** Catch validation exception */
        if ($error = $validator->run($this->request->from('name', 'password', 'mail', 'confirm'))) {
            Cookie::set('__typecho_remember_name', $this->request->get('name'));
            Cookie::set('__typecho_remember_mail', $this->request->get('mail'));

            /** Set notice message */
            Notice::alloc()->set($error);
            $this->response->goBack();
        }

        $hasher = new PasswordHash(8, true);
        $generatedPassword = Common::randString(7);

        $dataStruct = [
            'name' => $this->request->get('name'),
            'mail' => $this->request->get('mail'),
            'screenName' => $this->request->get('name'),
            'password' => $hasher->hashPassword($generatedPassword),
            'created' => $this->options->time,
            'group' => 'subscriber'
        ];

        $dataStruct = self::pluginHandle()->filter('register', $dataStruct);

        $insertId = $this->insert($dataStruct);
        $this->db->fetchRow($this->select()->where('uid = ?', $insertId)
            ->limit(1), [$this, 'push']);

        self::pluginHandle()->call('finishRegister', $this);

        $this->user->login($this->request->get('name'), $generatedPassword);

        Cookie::delete('__typecho_first_run');
        Cookie::delete('__typecho_remember_name');
        Cookie::delete('__typecho_remember_mail');

        Notice::alloc()->set(
            _t(
                'User <strong>%s</strong> registered successfully, password is <strong>%s</strong>.',
                $this->screenName,
                $generatedPassword
            ),
            'success'
        );
        $this->response->redirect($this->options->adminUrl);
    }
}
