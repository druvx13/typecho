<?php if (!defined('__TYPECHO_ADMIN__')) exit; ?>
<header class="typecho-head-nav" role="navigation">
    <nav>
        <details class="menu-bar">
            <summary><?php _e('Menu'); ?></summary>
        </details>
        <menu>
            <?php $menu->output(); ?>
            <li class="operate">
                <?php \Typecho\Plugin::factory('admin/menu.php')->call('navBar'); ?><a title="<?php
                if ($user->logged > 0) {
                    $logged = new \Typecho\Date($user->logged);
                    _e('Last login: %s', $logged->word());
                }
                ?>" href="<?php $options->adminUrl('profile.php'); ?>" class="author"><?php $user->screenName(); ?></a><a
                    class="exit" href="<?php $options->logoutUrl(); ?>"><?php _e('Logout'); ?></a><a
                    href="<?php $options->siteUrl(); ?>"><?php _e('Website'); ?></a>
            </li>
        </menu>
    </nav>
</header>

