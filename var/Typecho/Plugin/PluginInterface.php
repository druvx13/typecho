<?php

namespace Typecho\Plugin;

use Typecho\Widget\Helper\Form;

/**
 * Plugin interface
 *
 * @package Plugin
 * @abstract
 */
interface PluginInterface
{
    /**
     * Enable plugin方法,如果启用失败,直接抛出异常
     *
     * @static
     * @access public
     * @return void
     */
    public static function activate();

    /**
     * Plugin deactivation method; throws exception on failure
     *
     * @static
     * @access public
     * @return void
     */
    public static function deactivate();

    /**
     * Get plugin configuration panel
     *
     * @param Form $form Configuration panel
     */
    public static function config(Form $form);

    /**
     * Personal user configuration panel
     *
     * @param Form $form
     */
    public static function personalConfig(Form $form);
}
