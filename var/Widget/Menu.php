<?php

namespace Widget;

use Typecho\Common;
use Widget\Plugins\Config;
use Widget\Themes\Files;
use Widget\Users\Edit as UsersEdit;
use Widget\Contents\Attachment\Edit as AttachmentEdit;
use Widget\Contents\Post\Edit as PostEdit;
use Widget\Contents\Page\Edit as PageEdit;
use Widget\Contents\Post\Admin as PostAdmin;
use Widget\Contents\Page\Admin as PageAdmin;
use Widget\Comments\Admin as CommentsAdmin;
use Widget\Metas\Category\Admin as CategoryAdmin;
use Widget\Metas\Category\Edit as CategoryEdit;
use Widget\Metas\Tag\Admin as TagAdmin;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * Admin menu display
 *
 * @package Widget
 */
class Menu extends Base
{
    /**
     * 当前菜单Title
     * @var string
     */
    public string $title;

    /**
     * 当前增加项目链接
     * @var string|null
     */
    public ?string $addLink;

    /**
     * 父菜单列表
     *
     * @var array
     */
    private array $menu = [];

    /**
     * 当前父菜单
     *
     * @var integer
     */
    private int $currentParent = 1;

    /**
     * 当前子菜单
     *
     * @var integer
     */
    private int $currentChild = 0;

    /**
     * Current page
     *
     * @var string
     */
    private string $currentUrl;

    /**
     * 当前菜单URL
     *
     * @var string
     */
    private string $currentMenuUrl;

    /**
     * Execute action,初始化菜单
     */
    public function execute()
    {
        $parentNodes = [null, _t('Console'), _t('Write'), _t('Admin'), _t('Settings')];

        $childNodes = [
            [
                [_t('Login'), _t('Login to %s', $this->options->title), 'login.php', 'visitor'],
                [_t('Register'), _t('Register at %s', $this->options->title), 'register.php', 'visitor']
            ],
            [
                [_t('Overview'), _t('Website overview'), 'index.php', 'subscriber'],
                [_t('Preferences'), _t('Preferences'), 'profile.php', 'subscriber'],
                [_t('Plugins'), _t('manage plugins'), 'plugins.php', 'administrator'],
                [[Config::class, 'getMenuTitle'], [Config::class, 'getMenuTitle'], 'options-plugin.php?config=', 'administrator', true],
                [_t('Appearance'), _t('Website appearances'), 'themes.php', 'administrator'],
                [[Files::class, 'getMenuTitle'], [Files::class, 'getMenuTitle'], 'theme-editor.php', 'administrator', true],
                [_t('Setup Appearance'), _t('Setup Appearance'), 'options-theme.php', 'administrator', true],
                [_t('Backup'), _t('Backup'), 'backup.php', 'administrator'],
                [_t('Upgrade'), _t('Upgrade program'), 'upgrade.php', 'administrator', true],
                [_t('Welcome'), _t('Welcome'), 'welcome.php', 'subscriber', true]
            ],
            [
                [_t('Write a post'), _t('Write a new post'), 'write-post.php', 'contributor'],
                [[PostEdit::class, 'getMenuTitle'], [PostEdit::class, 'getMenuTitle'], 'write-post.php?cid=', 'contributor', true],
                [_t('Create a page'), _t('Create a new page'), 'write-page.php', 'editor'],
                [[PageEdit::class, 'getMenuTitle'], [PageEdit::class, 'getMenuTitle'], 'write-page.php?cid=', 'editor', true],
                [[PageEdit::class, 'getMenuTitle'], [PageEdit::class, 'getMenuTitle'], 'write-page.php?parent=', 'editor', true],
            ],
            [
                [_t('Posts'), _t('Manage posts'), 'manage-posts.php', 'contributor', false, 'write-post.php'],
                [[PostAdmin::class, 'getMenuTitle'], [PostAdmin::class, 'getMenuTitle'], 'manage-posts.php?uid=', 'contributor', true],
                [_t('Pages'), _t('Manage pages'), 'manage-pages.php', 'editor', false, 'write-page.php'],
                [[PageAdmin::class, 'getMenuTitle'], [PageAdmin::class, 'getMenuTitle'], 'manage-pages.php?parent=', 'editor', true, [PageAdmin::class, 'getAddLink']],
                [_t('Comments'), _t('Manage comments'), 'manage-comments.php', 'contributor'],
                [[CommentsAdmin::class, 'getMenuTitle'], [CommentsAdmin::class, 'getMenuTitle'], 'manage-comments.php?cid=', 'contributor', true],
                [_t('Category'), _t('Manage categories'), 'manage-categories.php', 'editor', false, 'category.php'],
                [_t('New category'), _t('New category'), 'category.php', 'editor', true],
                [[CategoryAdmin::class, 'getMenuTitle'], [CategoryAdmin::class, 'getMenuTitle'], 'manage-categories.php?parent=', 'editor', true, [CategoryAdmin::class, 'getAddLink']],
                [[CategoryEdit::class, 'getMenuTitle'], [CategoryEdit::class, 'getMenuTitle'], 'category.php?mid=', 'editor', true],
                [[CategoryEdit::class, 'getMenuTitle'], [CategoryEdit::class, 'getMenuTitle'], 'category.php?parent=', 'editor', true],
                [_t('Tag'), _t('Manage tags'), 'manage-tags.php', 'editor'],
                [[TagAdmin::class, 'getMenuTitle'], [TagAdmin::class, 'getMenuTitle'], 'manage-tags.php?mid=', 'editor', true],
                [_t('Files'), _t('Manage files'), 'manage-medias.php', 'editor'],
                [[AttachmentEdit::class, 'getMenuTitle'], [AttachmentEdit::class, 'getMenuTitle'], 'media.php?cid=', 'contributor', true],
                [_t('Users'), _t('Manage users'), 'manage-users.php', 'administrator', false, 'user.php'],
                [_t('Add users'), _t('Add users'), 'user.php', 'administrator', true],
                [[UsersEdit::class, 'getMenuTitle'], [UsersEdit::class, 'getMenuTitle'], 'user.php?uid=', 'administrator', true],
            ],
            [
                [_t('Basic'), _t('Basic settings'), 'options-general.php', 'administrator'],
                [_t('Comments'), _t('Comments settings'), 'options-discussion.php', 'administrator'],
                [_t('Read'), _t('Reading settings'), 'options-reading.php', 'administrator'],
                [_t('Permalink'), _t('Permalink settings'), 'options-permalink.php', 'administrator'],
            ]
        ];

        /** 获取扩展菜单 */
        $panelTable = $this->options->panelTable;
        $extendingParentMenu = empty($panelTable['parent']) ? [] : $panelTable['parent'];
        $extendingChildMenu = empty($panelTable['child']) ? [] : $panelTable['child'];
        $currentUrl = $this->request->getRequestUrl();
        $adminUrl = $this->options->adminUrl;
        $menu = [];
        $defaultChildNode = [null, null, null, 'administrator', false, null];

        $currentUrlParts = parse_url($currentUrl);
        $currentUrlParams = [];
        if (!empty($currentUrlParts['query'])) {
            parse_str($currentUrlParts['query'], $currentUrlParams);
        }

        if ('/' == $currentUrlParts['path'][strlen($currentUrlParts['path']) - 1]) {
            $currentUrlParts['path'] .= 'index.php';
        }

        foreach ($extendingParentMenu as $key => $val) {
            $parentNodes[10 + $key] = $val;
        }

        foreach ($extendingChildMenu as $key => $val) {
            $childNodes[$key] = array_merge($childNodes[$key] ?? [], $val);
        }

        foreach ($parentNodes as $key => $parentNode) {
            // this is a simple struct than before
            $children = [];
            $showedChildrenCount = 0;
            $firstUrl = null;

            foreach ($childNodes[$key] as $inKey => $childNode) {
                // magic merge
                $childNode += $defaultChildNode;
                [$name, $title, $url, $access] = $childNode;

                $hidden = $childNode[4] ?? false;
                $addLink = $childNode[5] ?? null;

                // 保存最原始的hidden信息
                $orgHidden = $hidden;

                // parse url
                $menuUrl = $url;
                $url = Common::url($url, $adminUrl);

                // compare url
                $urlParts = parse_url($url);
                $urlParams = [];
                if (!empty($urlParts['query'])) {
                    parse_str($urlParts['query'], $urlParams);
                }

                $validate = true;
                if ($urlParts['path'] != $currentUrlParts['path']) {
                    $validate = false;
                } else {
                    foreach ($urlParams as $paramName => $paramValue) {
                        if (!isset($currentUrlParams[$paramName])) {
                            $validate = false;
                            break;
                        }
                    }
                }

                if (
                    $validate
                    && basename($urlParts['path']) == 'extending.php'
                    && !empty($currentUrlParams['panel']) && !empty($urlParams['panel'])
                    && $urlParams['panel'] != $currentUrlParams['panel']
                ) {
                    $validate = false;
                }

                if ($hidden && $validate) {
                    $hidden = false;
                }

                if (!$hidden && !$this->user->pass($access, true)) {
                    $hidden = true;
                }

                if (!$hidden) {
                    $showedChildrenCount++;

                    if (empty($firstUrl)) {
                        $firstUrl = $url;
                    }

                    if (is_array($name)) {
                        [$widget, $method] = $name;
                        $name = self::widget($widget)->$method();
                    }

                    if (is_array($title)) {
                        [$widget, $method] = $title;
                        $title = self::widget($widget)->$method();
                    }

                    if (is_array($addLink)) {
                        [$widget, $method] = $addLink;
                        $addLink = self::widget($widget)->$method();
                    }
                }

                if ($validate) {
                    if ('visitor' != $access) {
                        $this->user->pass($access);
                    }

                    $this->currentParent = $key;
                    $this->currentChild = $inKey;
                    $this->title = $title;
                    $this->addLink = $addLink ? Common::url($addLink, $adminUrl) : null;
                    $this->currentMenuUrl = $menuUrl;
                }

                $children[$inKey] = [
                    $name,
                    $title,
                    $url,
                    $access,
                    $hidden,
                    $addLink,
                    $orgHidden
                ];
            }

            $menu[$key] = [$parentNode, $showedChildrenCount > 0, $firstUrl, $children];
        }

        $this->menu = $menu;
        $this->currentUrl = Common::safeUrl($currentUrl);
    }

    /**
     * 获取当前菜单
     *
     * @return array
     */
    public function getCurrentMenu(): ?array
    {
        return $this->currentParent > 0 ? $this->menu[$this->currentParent][3][$this->currentChild] : null;
    }

    /**
     * 获取当前菜单URL
     *
     * @return string
     */
    public function getCurrentMenuUrl(): string
    {
        return $this->currentMenuUrl;
    }

    /**
     * 输出父级菜单
     */
    public function output($class = 'focus', $childClass = 'focus')
    {
        foreach ($this->menu as $key => $node) {
            if (!$node[1] || !$key) {
                continue;
            }

            echo "<li" . ($key == $this->currentParent ? " class=\"{$class}\"" : '')
                . "><a href=\"{$node[2]}\">{$node[0]}</a>"
                . "<menu>";

            foreach ($node[3] as $inKey => $inNode) {
                if ($inNode[4]) {
                    continue;
                }

                $focus = false;
                if ($key == $this->currentParent && $inKey == $this->currentChild) {
                    $focus = true;
                } elseif ($inNode[6]) {
                    continue;
                }

                echo "<li" . ($focus ? " class=\"{$childClass}\"" : '') . "><a href=\""
                    . ($key == $this->currentParent && $inKey == $this->currentChild ? $this->currentUrl : $inNode[2])
                    . "\">{$inNode[0]}</a></li>";
            }

            echo '</menu></li>';
        }
    }
}
