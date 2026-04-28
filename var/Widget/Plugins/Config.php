<?php

namespace Widget\Plugins;

use Typecho\Plugin;
use Typecho\Widget\Exception;
use Typecho\Widget\Helper\Form;
use Widget\Base\Options;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * 插件配置组件
 *
 * @author qining
 * @category typecho
 * @package Widget
 * @copyright Copyright (c) 2008 Typecho team (http://www.typecho.org)
 * @license GNU General Public License 2.0
 */
class Config extends Options
{
    /**
     * Get plugin info
     *
     * @var array
     */
    public array $info;

    /**
     * 插件File path
     *
     * @var string
     */
    private string $pluginFileName;

    /**
     * 插件类
     *
     * @var string
     */
    private string $className;

    /**
     * Bind action
     *
     * @throws Plugin\Exception
     * @throws Exception|\Typecho\Db\Exception
     */
    public function execute()
    {
        $this->user->pass('administrator');
        $config = $this->request->filter('slug')->get('config');
        if (empty($config)) {
            throw new Exception(_t('Plugin does not exist.'), 404);
        }

        /** Get plugin entry point */
        [$this->pluginFileName, $this->className] = Plugin::portal($config, $this->options->pluginDir);
        $this->info = Plugin::parseInfo($this->pluginFileName);
    }

    /**
     * Get menu title
     *
     * @return string
     */
    public function getMenuTitle(): string
    {
        return _t('Setup plugin %s', $this->info['title']);
    }

    /**
     * Configure plugin
     *
     * @return Form
     * @throws Exception|Plugin\Exception
     */
    public function config(): Form
    {
        /** Get plugin name */
        $pluginName = $this->request->filter('slug')->get('config');

        /** Get enabled plugins */
        $plugins = Plugin::export();
        $activatedPlugins = $plugins['activated'];

        /** Check whether instantiation succeeded */
        if (!$this->info['config'] || !isset($activatedPlugins[$pluginName])) {
            throw new Exception(_t('Cannot configure plugin.'), 500);
        }

        /** Load plugin */
        require_once $this->pluginFileName;
        $form = new Form($this->security->getIndex('/action/plugins-edit?config=' . $pluginName), Form::POST_METHOD);
        call_user_func([$this->className, 'config'], $form);

        $options = $this->options->plugin($pluginName);

        if (!empty($options)) {
            foreach ($options as $key => $val) {
                $form->getInput($key)->value($val);
            }
        }

        $submit = new Form\Element\Submit(null, null, _t('Save settings.'));
        $submit->input->setAttribute('class', 'btn primary');
        $form->addItem($submit);
        return $form;
    }
}
