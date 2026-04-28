<?php

namespace Utils;

use Typecho\Common;
use Typecho\Db;
use Typecho\I18n;
use Typecho\Plugin;
use Typecho\Widget;
use Widget\Base\Options as BaseOptions;
use Widget\Options;
use Widget\Plugins\Edit;
use Widget\Security;
use Widget\Service;

/**
 * The plugin helper ships with all Typecho distributions.
 * You can safely use its features to help install plugins on user systems.
 *
 * @package Helper
 * @author qining
 * @version 1.0.0
 * @link http://typecho.org
 */
class Helper
{
    /**
     * Get Security object
     *
     * @return Security
     */
    public static function security(): Security
    {
        return Security::alloc();
    }

    /**
     * Get a single Widget object by ID
     *
     * @param string $table Table name; supports: contents, comments, metas, users
     * @param int $pkId
     * @return Widget|null
     */
    public static function widgetById(string $table, int $pkId): ?Widget
    {
        $table = ucfirst($table);
        if (!in_array($table, ['Contents', 'Comments', 'Metas', 'Users'])) {
            return null;
        }

        $keys = [
            'Contents' => 'cid',
            'Comments' => 'coid',
            'Metas'    => 'mid',
            'Users'    => 'uid'
        ];

        $className = '\Widget\Base\\' . $table;

        $key = $keys[$table];
        $db = Db::get();
        $widget = Widget::widget($className . '@' . $pkId);

        $db->fetchRow(
            $widget->select()->where("{$key} = ?", $pkId)->limit(1),
            [$widget, 'push']
        );

        return $widget;
    }

    /**
     * Request async service
     *
     * @param $method
     * @param $params
     */
    public static function requestService($method, ... $params)
    {
        Service::alloc()->requestService($method, ... $params);
    }

    /**
     * Force-delete a plugin
     *
     * @param string $pluginName Plugin name
     */
    public static function removePlugin(string $pluginName)
    {
        try {
            /** Get plugin entry point */
            [$pluginFileName, $className] = Plugin::portal(
                $pluginName,
                __TYPECHO_ROOT_DIR__ . '/' . __TYPECHO_PLUGIN_DIR__
            );

            /** Get enabled plugins */
            $plugins = Plugin::export();
            $activatedPlugins = $plugins['activated'];

            /** Load plugin */
            require_once $pluginFileName;

            /** Check whether instantiation succeeded */
            if (
                !isset($activatedPlugins[$pluginName]) || !class_exists($className)
                || !method_exists($className, 'deactivate')
            ) {
                throw new Widget\Exception(_t('Cannot disable the plugin.'), 500);
            }

            call_user_func([$className, 'deactivate']);
        } catch (\Exception $e) {
            //nothing to do
        }

        $db = Db::get();

        try {
            Plugin::deactivate($pluginName);
            self::setOption('plugins', Plugin::export());
        } catch (Plugin\Exception $e) {
            //nothing to do
        }

        $db->query($db->delete('table.options')->where('name = ?', 'plugin:' . $pluginName));
    }

    /**
     * Import language strings
     *
     * @param string $domain
     */
    public static function lang(string $domain)
    {
        $currentLang = I18n::getLang();
        if ($currentLang) {
            $currentLang = basename($currentLang);
            $fileName = dirname(__FILE__) . '/' . $domain . '/lang/' . $currentLang;
            if (file_exists($fileName)) {
                I18n::addLang($fileName);
            }
        }
    }

    /**
     * Get Options object
     *
     * @return Options
     */
    public static function options(): Options
    {
        return Options::alloc();
    }

    /**
     * @param string $name
     * @param $value
     * @return int
     */
    public static function setOption(string $name, $value): int
    {
        $options = self::options();
        $options->{$name} = $value;

        return BaseOptions::alloc()->update(
            ['value' => is_array($value) ? json_encode($value) : $value],
            Db::get()->sql()->where('name = ?', $name)
        );
    }

    /**
     * Add route
     *
     * @param string $name Route name
     * @param string $url Route path
     * @param string $widget Widget name
     * @param string|null $action Widget action
     * @param string|null $after Insert after this route
     * @return integer
     */
    public static function addRoute(
        string $name,
        string $url,
        string $widget,
        ?string $action = null,
        ?string $after = null
    ): int {
        $routingTable = self::options()->routingTable;
        if (isset($routingTable[0])) {
            unset($routingTable[0]);
        }

        $pos = 0;
        foreach ($routingTable as $key => $val) {
            $pos++;

            if ($key == $after) {
                break;
            }
        }

        $pre = array_slice($routingTable, 0, $pos);
        $next = array_slice($routingTable, $pos);

        $routingTable = array_merge($pre, [
            $name => [
                'url'    => $url,
                'widget' => $widget,
                'action' => $action
            ]
        ], $next);

        return self::setOption('routingTable', $routingTable);
    }

    /**
     * Remove route
     *
     * @param string $name Route name
     * @return integer
     */
    public static function removeRoute(string $name): int
    {
        $routingTable = self::options()->routingTable;
        if (isset($routingTable[0])) {
            unset($routingTable[0]);
        }

        unset($routingTable[$name]);
        return self::setOption('routingTable', $routingTable);
    }

    /**
     * Add action extension
     *
     * @param string $actionName Action name to extend
     * @param string $widgetName Widget name to extend
     * @return integer
     */
    public static function addAction(string $actionName, string $widgetName): int
    {
        $actionTable = self::options()->actionTable;
        $actionTable = empty($actionTable) ? [] : $actionTable;
        $actionTable[$actionName] = $widgetName;

        return self::setOption('actionTable', $actionTable);
    }

    /**
     * Remove action extension
     *
     * @param string $actionName
     * @return int
     */
    public static function removeAction(string $actionName): int
    {
        $actionTable = self::options()->actionTable;
        $actionTable = empty($actionTable) ? [] : $actionTable;

        if (isset($actionTable[$actionName])) {
            unset($actionTable[$actionName]);
            reset($actionTable);
        }

        return self::setOption('actionTable', $actionTable);
    }

    /**
     * Add a menu
     *
     * @param string $menuName Menu name
     * @return integer
     */
    public static function addMenu(string $menuName): int
    {
        $panelTable = self::options()->panelTable;
        $panelTable['parent'] = empty($panelTable['parent']) ? [] : $panelTable['parent'];
        $panelTable['parent'][] = $menuName;

        self::setOption('panelTable', $panelTable);

        end($panelTable['parent']);
        return key($panelTable['parent']) + 10;
    }

    /**
     * Remove a menu
     *
     * @param string $menuName Menu name
     * @return integer
     */
    public static function removeMenu(string $menuName): int
    {
        $panelTable = self::options()->panelTable;
        $panelTable['parent'] = empty($panelTable['parent']) ? [] : $panelTable['parent'];

        if (false !== ($index = array_search($menuName, $panelTable['parent']))) {
            unset($panelTable['parent'][$index]);
        }

        self::setOption('panelTable', $panelTable);

        return $index + 10;
    }

    /**
     * Add a panel
     *
     * @param integer $index Menu index
     * @param string $fileName File name
     * @param string $title Panel title
     * @param string $subTitle Panel subtitle
     * @param string $level Access permission
     * @param boolean $hidden Whether to hide
     * @param string $addLink Link for adding new items, shown after the page title
     * @return integer
     */
    public static function addPanel(
        int $index,
        string $fileName,
        string $title,
        string $subTitle,
        string $level,
        bool $hidden = false,
        string $addLink = ''
    ): int {
        $panelTable = self::options()->panelTable;
        $panelTable['child'] = empty($panelTable['child']) ? [] : $panelTable['child'];
        $panelTable['child'][$index] = empty($panelTable['child'][$index]) ? [] : $panelTable['child'][$index];
        $fileName = urlencode(trim($fileName, '/'));
        $panelTable['child'][$index][]
            = [$title, $subTitle, 'extending.php?panel=' . $fileName, $level, $hidden, $addLink];

        $panelTable['file'] = empty($panelTable['file']) ? [] : $panelTable['file'];
        $panelTable['file'][] = $fileName;
        $panelTable['file'] = array_unique($panelTable['file']);

        self::setOption('panelTable', $panelTable);

        end($panelTable['child'][$index]);
        return key($panelTable['child'][$index]);
    }

    /**
     * Remove a panel
     *
     * @param integer $index Menu index
     * @param string $fileName File name
     * @return integer
     */
    public static function removePanel(int $index, string $fileName): int
    {
        $panelTable = self::options()->panelTable;
        $panelTable['child'] = empty($panelTable['child']) ? [] : $panelTable['child'];
        $panelTable['child'][$index] = empty($panelTable['child'][$index]) ? [] : $panelTable['child'][$index];
        $panelTable['file'] = empty($panelTable['file']) ? [] : $panelTable['file'];
        $fileName = urlencode(trim($fileName, '/'));

        if (false !== ($key = array_search($fileName, $panelTable['file']))) {
            unset($panelTable['file'][$key]);
        }

        $return = 0;
        foreach ($panelTable['child'][$index] as $key => $val) {
            if ($val[2] == 'extending.php?panel=' . $fileName) {
                unset($panelTable['child'][$index][$key]);
                $return = $key;
            }
        }

        self::setOption('panelTable', $panelTable);
        return $return;
    }

    /**
     * Get panel URL
     *
     * @param string $fileName
     * @return string
     */
    public static function url(string $fileName): string
    {
        return Common::url('extending.php?panel=' . (trim($fileName, '/')), self::options()->adminUrl);
    }

    /**
     * Manually configure plugin variables
     *
     * @param mixed $pluginName Plugin name
     * @param array $settings Variable key-value pairs
     * @param bool $isPersonal . (default: false) Whether this is a private variable
     */
    public static function configPlugin($pluginName, array $settings, bool $isPersonal = false)
    {
        if (empty($settings)) {
            return;
        }

        Edit::configPlugin($pluginName, $settings, $isPersonal);
    }

    /**
     * Comment reply button
     *
     * @access public
     * @param string $theId Comment element ID
     * @param integer $coid Comment ID
     * @param string $word Button text
     * @param string $formId Form ID
     * @param integer $style Style type
     * @return void
     */
    public static function replyLink(
        string $theId,
        int $coid,
        string $word = 'Reply',
        string $formId = 'respond',
        int $style = 2
    ) {
        if (self::options()->commentsThreaded) {
            echo '<a href="#' . $formId . '" rel="nofollow" onclick="return typechoAddCommentReply(\'' .
                $theId . '\', ' . $coid . ', \'' . $formId . '\', ' . $style . ');">' . $word . '</a>';
        }
    }

    /**
     * Comment cancel button
     *
     * @param string $word Button text
     * @param string $formId Form ID
     */
    public static function cancelCommentReplyLink(string $word = 'Cancel', string $formId = 'respond')
    {
        if (self::options()->commentsThreaded) {
            echo '<a href="#' . $formId . '" rel="nofollow" onclick="return typechoCancelCommentReply(\'' .
                $formId . '\');">' . $word . '</a>';
        }
    }

    /**
     * Comment reply JS script
     */
    public static function threadedCommentsScript()
    {
        if (self::options()->commentsThreaded) {
            echo
            <<<EOF
<script type="text/javascript">
var typechoAddCommentReply = function (cid, coid, cfid, style) {
    var _ce = document.getElementById(cid), _cp = _ce.parentNode;
    var _cf = document.getElementById(cfid);

    var _pi = document.getElementById('comment-parent');
    if (null == _pi) {
        _pi = document.createElement('input');
        _pi.setAttribute('type', 'hidden');
        _pi.setAttribute('name', 'parent');
        _pi.setAttribute('id', 'comment-parent');

        var _form = 'form' == _cf.tagName ? _cf : _cf.getElementsByTagName('form')[0];

        _form.appendChild(_pi);
    }
    _pi.setAttribute('value', coid);

    if (null == document.getElementById('comment-form-place-holder')) {
        var _cfh = document.createElement('div');
        _cfh.setAttribute('id', 'comment-form-place-holder');
        _cf.parentNode.insertBefore(_cfh, _cf);
    }

    1 == style ? (null == _ce.nextSibling ? _cp.appendChild(_cf)
    : _cp.insertBefore(_cf, _ce.nextSibling)) : _ce.appendChild(_cf);

    return false;
};

var typechoCancelCommentReply = function (cfid) {
    var _cf = document.getElementById(cfid),
    _cfh = document.getElementById('comment-form-place-holder');

    var _pi = document.getElementById('comment-parent');
    if (null != _pi) {
        _pi.parentNode.removeChild(_pi);
    }

    if (null == _cfh) {
        return true;
    }

    _cfh.parentNode.insertBefore(_cf, _cfh);
    return false;
};
</script>
EOF;
        }
    }
}
