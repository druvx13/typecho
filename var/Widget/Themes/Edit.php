<?php

namespace Widget\Themes;

use Typecho\Common;
use Typecho\Widget\Exception;
use Typecho\Widget\Helper\Form;
use Widget\ActionInterface;
use Widget\Base\Options;
use Widget\Notice;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 编辑风格组件
 *
 * @author qining
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Edit extends Options implements ActionInterface
{
    /**
     * 更换外观
     *
     * @param string $theme Theme name
     * @throws Exception
     * @throws \Typecho\Db\Exception
     */
    public function changeTheme(string $theme)
    {
        $theme = trim($theme, './');
        if (is_dir($this->options->themeFile($theme))) {
            /** 删除原外观设置信息 */
            $oldTheme = $this->options->missingTheme ?: $this->options->theme;
            $this->delete($this->db->sql()->where('name = ?', 'theme:' . $oldTheme));

            $this->update(['value' => $theme], $this->db->sql()->where('name = ?', 'theme'));

            /** Dissociate homepage */
            if (0 === strpos($this->options->frontPage, 'file:')) {
                $this->update(['value' => 'recent'], $this->db->sql()->where('name = ?', 'frontPage'));
            }

            $this->options->themeUrl = $this->options->themeUrl(null, $theme);

            $configFile = $this->options->themeFile($theme, 'functions.php');

            if (file_exists($configFile)) {
                require_once $configFile;

                if (function_exists('themeConfig')) {
                    $form = new Form();
                    themeConfig($form);
                    $options = $form->getValues();

                    if ($options && !$this->configHandle($options, true)) {
                        $this->insert([
                            'name'  => 'theme:' . $theme,
                            'value' => json_encode($options),
                            'user'  => 0
                        ]);
                    }
                }
            }

            Notice::alloc()->highlight('theme-' . $theme);
            Notice::alloc()->set(_t("Appearance changed."), 'success');
            $this->response->goBack();
        } else {
            throw new Exception(_t('The chosen style does not exist.'));
        }
    }

    /**
     * Process configuration value with own function
     *
     * @param array $settings Configuration values
     * @param boolean $isInit Whether this is initialization
     * @return boolean
     */
    public function configHandle(array $settings, bool $isInit): bool
    {
        if (function_exists('themeConfigHandle')) {
            themeConfigHandle($settings, $isInit);
            return true;
        }

        return false;
    }

    /**
     * 编辑外观文件
     *
     * @param string $theme Theme name
     * @param string $file File name
     * @throws Exception
     */
    public function editThemeFile(string $theme, string $file)
    {
        $path = $this->options->themeFile($theme, $file);

        if (
            file_exists($path) && is_writable($path)
            && (!defined('__TYPECHO_THEME_WRITEABLE__') || __TYPECHO_THEME_WRITEABLE__)
        ) {
            $handle = fopen($path, 'wb');
            if ($handle && fwrite($handle, $this->request->get('content'))) {
                fclose($handle);
                Notice::alloc()->set(_t("File %s saved.", $file), 'success');
            } else {
                Notice::alloc()->set(_t("Cannot write file %s.", $file), 'error');
            }
            $this->response->goBack();
        } else {
            throw new Exception(_t('The file you are editing does not exist.'));
        }
    }

    /**
     * Configure theme
     *
     * @param string $theme 外观名
     * @throws \Typecho\Db\Exception
     */
    public function config(string $theme)
    {
        // 已经载入了外观函数
        $form = Config::alloc()->config();

        /** Validate form */
        if (!Config::isExists($theme) || $form->validate()) {
            $this->response->goBack();
        }

        $settings = $form->getAllRequest();

        if (!$this->configHandle($settings, false)) {
            if ($this->options->__get('theme:' . $theme)) {
                $this->update(
                    ['value' => json_encode($settings)],
                    $this->db->sql()->where('name = ?', 'theme:' . $theme)
                );
            } else {
                $this->insert([
                    'name'  => 'theme:' . $theme,
                    'value' => json_encode($settings),
                    'user'  => 0
                ]);
            }
        }

        /** Set highlight */
        Notice::alloc()->highlight('theme-' . $theme);

        /** Notice message */
        Notice::alloc()->set(_t("Appearance settings saved."), 'success');

        /** Redirect to original page */
        $this->response->redirect(Common::url('options-theme.php', $this->options->adminUrl));
    }

    /**
     * Bind action
     *
     * @throws Exception|\Typecho\Db\Exception
     */
    public function action()
    {
        /** 需要管理员权限 */
        $this->user->pass('administrator');
        $this->security->protect();
        $this->on($this->request->is('change'))->changeTheme($this->request->filter('slug')->get('change'));
        $this->on($this->request->is('edit&theme'))
            ->editThemeFile($this->request->filter('slug')->get('theme'), $this->request->get('edit'));
        $this->on($this->request->is('config'))->config($this->request->filter('slug')->get('config'));
        $this->response->redirect($this->options->adminUrl);
    }
}
