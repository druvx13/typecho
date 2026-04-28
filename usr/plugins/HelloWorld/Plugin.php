<?php

namespace TypechoPlugin\HelloWorld;

use Typecho\Plugin\PluginInterface;
use Typecho\Widget\Helper\Form;
use Typecho\Widget\Helper\Form\Element\Text;
use Widget\Options;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * Hello World
 *
 * @package HelloWorld
 * @author qining
 * @version 1.0.0
 * @link http://typecho.org
 */
class Plugin implements PluginInterface
{
    /**
     * Plugin activation method; throws exception on failure
     */
    public static function activate()
    {
        \Typecho\Plugin::factory('admin/menu.php')->navBar = __CLASS__ . '::render';
    }

    /**
     * Plugin deactivation method; throws exception on failure
     */
    public static function deactivate()
    {
    }

    /**
     * Get plugin configuration panel
     *
     * @param Form $form Configuration panel
     */
    public static function config(Form $form)
    {
        /** Category name */
        $name = new Text('word', null, 'Hello World', _t('Say something'));
        $form->addInput($name);
    }

    /**
     * Personal user configuration panel
     *
     * @param Form $form
     */
    public static function personalConfig(Form $form)
    {
    }

    /**
     * Plugin implementation method
     *
     * @access public
     * @return void
     */
    public static function render()
    {
        echo '<span class="message success">'
            . htmlspecialchars(Options::alloc()->plugin('HelloWorld')->word)
            . '</span>';
    }
}
