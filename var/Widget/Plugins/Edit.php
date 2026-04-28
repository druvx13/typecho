<?php

namespace Widget\Plugins;

use Typecho\Common;
use Typecho\Db;
use Typecho\Plugin;
use Typecho\Widget\Exception;
use Typecho\Widget\Helper\Form;
use Widget\ActionInterface;
use Widget\Base\Options;
use Widget\Notice;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 插件管理组件
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
     * @var bool
     */
    private bool $configNoticed = false;

    /**
     * Enable plugin
     *
     * @param $pluginName
     * @throws Exception|Db\Exception|Plugin\Exception
     */
    public function activate($pluginName)
    {
        /** Get plugin entry point */
        [$pluginFileName, $className] = Plugin::portal($pluginName, $this->options->pluginDir);
        $info = Plugin::parseInfo($pluginFileName);

        /** 检测依赖信息 */
        if (Plugin::checkDependence($info['since'])) {

            /** Get enabled plugins */
            $plugins = Plugin::export();
            $activatedPlugins = $plugins['activated'];

            /** Load plugin */
            require_once $pluginFileName;

            /** Check whether instantiation succeeded */
            if (
                isset($activatedPlugins[$pluginName]) || !class_exists($className)
                || !method_exists($className, 'activate')
            ) {
                throw new Exception(_t('Cannot enable plugins.'), 500);
            }

            try {
                $result = call_user_func([$className, 'activate']);
                Plugin::activate($pluginName);
                $this->update(
                    ['value' => json_encode(Plugin::export())],
                    $this->db->sql()->where('name = ?', 'plugins')
                );
            } catch (Plugin\Exception $e) {
                /** Catch exception */
                Notice::alloc()->set($e->getMessage(), 'error');
                $this->response->goBack();
            }

            $form = new Form();
            call_user_func([$className, 'config'], $form);

            $personalForm = new Form();
            call_user_func([$className, 'personalConfig'], $personalForm);

            $options = $form->getValues();
            $personalOptions = $personalForm->getValues();

            if ($options && !$this->configHandle($pluginName, $options, true)) {
                self::configPlugin($pluginName, $options);
            }

            if ($personalOptions && !$this->personalConfigHandle($className, $personalOptions)) {
                self::configPlugin($pluginName, $personalOptions, true);
            }
        } else {
            $result = _t('<a href="%s">%s</a> cannot work under this version of typecho.', $info['homepage'], $info['title']);
        }

        /** Set highlight */
        Notice::alloc()->highlight('plugin-' . $pluginName);

        if (isset($result) && is_string($result)) {
            Notice::alloc()->set($result, 'notice');
        } else {
            Notice::alloc()->set(_t('Plugin enabled.'), 'success');
        }
        $this->response->goBack();
    }

    /**
     * Process configuration value with own function
     *
     * @access public
     * @param string $pluginName Plugin name
     * @param array $settings Configuration values
     * @param boolean $isInit Whether this is initialization
     * @return boolean
     * @throws Plugin\Exception
     */
    public function configHandle(string $pluginName, array $settings, bool $isInit): bool
    {
        /** Get plugin entry point */
        [$pluginFileName, $className] = Plugin::portal($pluginName, $this->options->pluginDir);

        if (!$isInit && method_exists($className, 'configCheck')) {
            $result = call_user_func([$className, 'configCheck'], $settings);

            if (!empty($result) && is_string($result)) {
                Notice::alloc()->set($result);
                $this->configNoticed = true;
            }
        }

        if (method_exists($className, 'configHandle')) {
            call_user_func([$className, 'configHandle'], $settings, $isInit);
            return true;
        }

        return false;
    }

    /**
     * Manually configure plugin variables
     *
     * @param string $pluginName Plugin name
     * @param array $settings Variable key-value pairs
     * @param bool $isPersonal Whether this is a private variable
     * @throws Db\Exception
     */
    public static function configPlugin(string $pluginName, array $settings, bool $isPersonal = false)
    {
        $db = Db::get();
        $pluginName = ($isPersonal ? '_' : '') . 'plugin:' . $pluginName;

        $select = $db->select()->from('table.options')
            ->where('name = ?', $pluginName);

        $options = $db->fetchAll($select);

        if (empty($settings)) {
            if (!empty($options)) {
                $db->query($db->delete('table.options')->where('name = ?', $pluginName));
            }
        } else {
            if (empty($options)) {
                $db->query($db->insert('table.options')
                    ->rows([
                        'name'  => $pluginName,
                        'value' => json_encode($settings),
                        'user'  => 0
                    ]));
            } else {
                foreach ($options as $option) {
                    $value = json_decode($option['value'], true);
                    $value = array_merge($value, $settings);

                    $db->query($db->update('table.options')
                        ->rows(['value' => json_encode($value)])
                        ->where('name = ?', $pluginName)
                        ->where('user = ?', $option['user']));
                }
            }
        }
    }

    /**
     * Process custom configuration value with own function
     *
     * @param string $className Class name
     * @param array $settings Configuration values
     * @return boolean
     */
    public function personalConfigHandle(string $className, array $settings): bool
    {
        if (method_exists($className, 'personalConfigHandle')) {
            call_user_func([$className, 'personalConfigHandle'], $settings, true);
            return true;
        }

        return false;
    }

    /**
     * Disable plugin
     *
     * @param string $pluginName
     * @throws Db\Exception
     * @throws Exception
     * @throws Plugin\Exception
     */
    public function deactivate(string $pluginName)
    {
        /** Get enabled plugins */
        $plugins = Plugin::export();
        $activatedPlugins = $plugins['activated'];
        $pluginFileExist = true;

        try {
            /** Get plugin entry point */
            [$pluginFileName, $className] = Plugin::portal($pluginName, $this->options->pluginDir);
        } catch (Plugin\Exception $e) {
            $pluginFileExist = false;

            if (!isset($activatedPlugins[$pluginName])) {
                throw $e;
            }
        }

        /** Check whether instantiation succeeded */
        if (!isset($activatedPlugins[$pluginName])) {
            throw new Exception(_t('Cannot disable the plugin.'), 500);
        }

        if ($pluginFileExist) {

            /** Load plugin */
            require_once $pluginFileName;

            /** Check whether instantiation succeeded */
            if (
                !isset($activatedPlugins[$pluginName]) || !class_exists($className)
                || !method_exists($className, 'deactivate')
            ) {
                throw new Exception(_t('Cannot disable the plugin.'), 500);
            }

            try {
                $result = call_user_func([$className, 'deactivate']);
            } catch (Plugin\Exception $e) {
                /** Catch exception */
                Notice::alloc()->set($e->getMessage(), 'error');
                $this->response->goBack();
            }

            /** Set highlight */
            Notice::alloc()->highlight('plugin-' . $pluginName);
        }

        Plugin::deactivate($pluginName);
        $this->update(['value' => json_encode(Plugin::export())], $this->db->sql()->where('name = ?', 'plugins'));

        $this->delete($this->db->sql()->where('name = ?', 'plugin:' . $pluginName));
        $this->delete($this->db->sql()->where('name = ?', '_plugin:' . $pluginName));

        if (isset($result) && is_string($result)) {
            Notice::alloc()->set($result);
        } else {
            Notice::alloc()->set(_t('Plugin disabled.'), 'success');
        }
        $this->response->goBack();
    }

    /**
     * Configure plugin
     *
     * @param string $pluginName
     * @throws Db\Exception
     * @throws Exception
     * @throws Plugin\Exception
     */
    public function config(string $pluginName)
    {
        $form = Config::alloc()->config();

        /** Validate form */
        if ($form->validate()) {
            $this->response->goBack();
        }

        $settings = $form->getAllRequest();

        if (!$this->configHandle($pluginName, $settings, false)) {
            self::configPlugin($pluginName, $settings);
        }

        /** Set highlight */
        Notice::alloc()->highlight('plugin-' . $pluginName);

        if (!$this->configNoticed) {
            /** Notice message */
            Notice::alloc()->set(_t("Plugin settings saved."), 'success');
        }

        /** Redirect to original page */
        $this->response->redirect(Common::url('plugins.php', $this->options->adminUrl));
    }

    /**
     * Bind action
     */
    public function action()
    {
        $this->user->pass('administrator');
        $this->security->protect();
        $this->on($this->request->is('activate'))->activate($this->request->filter('slug')->get('activate'));
        $this->on($this->request->is('deactivate'))->deactivate($this->request->filter('slug')->get('deactivate'));
        $this->on($this->request->is('config'))->config($this->request->filter('slug')->get('config'));
        $this->response->redirect($this->options->adminUrl);
    }
}
