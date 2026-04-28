<?php

namespace Widget;

use Typecho\Cookie;
use Typecho\Validate;
use Widget\Base\Users;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * Login widget
 *
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Login extends Users implements ActionInterface
{
    /**
     * Initialization function
     *
     * @access public
     * @return void
     */
    public function action()
    {
        // protect
        $this->security->protect();

        /** If already logged in */
        if ($this->user->hasLogin()) {
            /** Return directly */
            $this->response->redirect($this->options->index);
        }

        /** Initialize validation class */
        $validator = new Validate();
        $validator->addRule('name', 'required', _t('Please enter username'));
        $validator->addRule('password', 'required', _t('Please enter password'));
        $expire = 30 * 24 * 3600;

        /** 记住密码状态 */
        if ($this->request->is('remember=1')) {
            Cookie::set('__typecho_remember_remember', 1, $expire);
        } elseif (Cookie::get('__typecho_remember_remember')) {
            Cookie::delete('__typecho_remember_remember');
        }

        /** Catch validation exception */
        if ($error = $validator->run($this->request->from('name', 'password'))) {
            Cookie::set('__typecho_remember_name', $this->request->get('name'));

            /** Set notice message */
            Notice::alloc()->set($error);
            $this->response->goBack();
        }

        /** Begin user validation **/
        $valid = $this->user->login(
            $this->request->get('name'),
            $this->request->get('password'),
            false,
            $this->request->is('remember=1') ? $expire : 0
        );

        /** 比对密码 */
        if (!$valid) {
            /** 防止穷举,休眠3秒 */
            sleep(3);

            self::pluginHandle()->call(
                'loginFailure',
                $this->user,
                $this->request->get('name'),
                $this->request->get('password'),
                $this->request->is('remember=1')
            );

            Cookie::set('__typecho_remember_name', $this->request->get('name'));
            Notice::alloc()->set(_t('Username or password is invalid.'), 'error');
            $this->response->goBack('?referer=' . urlencode($this->request->get('referer')));
        }

        self::pluginHandle()->call(
            'loginSuccess',
            $this->user,
            $this->request->get('name'),
            $this->request->get('password'),
            $this->request->is('remember=1')
        );

        /** 跳转验证后地址 */
        if (!empty($this->request->referer)) {
            /** fix #952 & validate redirect url */
            if (
                0 === strpos($this->request->referer, $this->options->adminUrl)
                || 0 === strpos($this->request->referer, $this->options->siteUrl)
            ) {
                $this->response->redirect($this->request->referer);
            }
        } elseif (!$this->user->pass('contributor', true)) {
            /** 不允许普通用户直接跳转后台 */
            $this->response->redirect($this->options->profileUrl);
        }

        $this->response->redirect($this->options->adminUrl);
    }
}
