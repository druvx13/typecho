<?php
include 'common.php';
include 'header.php';
include 'menu.php';
?>

<main class="main">
    <div class="body container">
        <?php include 'page-title.php'; ?>
        <?php include 'theme-tabs.php'; ?>
        <div class="row typecho-page-main" role="main">
            <div class="col-mb-12">
                <table class="typecho-list-table typecho-theme-list">
                    <colgroup>
                        <col width="35%"/>
                        <col/>
                    </colgroup>

                    <thead>
                    <th><?php _e('Screenshot'); ?></th>
                    <th><?php _e('Details'); ?></th>
                    </thead>

                    <tbody>
                    <?php if ($options->missingTheme): ?>
                        <tr id="theme-<?php $options->missingTheme; ?>" class="current">
                            <td colspan="2" class="warning">
                                <p><strong><?php _e('The previously used theme "%s" could not be found. You can re-upload it or activate a different theme.', $options->missingTheme); ?></strong></p>
                                <ul>
                                    <li><?php _e('Re-upload this theme and refresh the page; this notice will disappear.'); ?></li>
                                    <li><?php _e('Enabling a new theme will delete the current theme's settings data.'); ?></li>
                                </ul>
                            </td>
                        </tr>
                    <?php endif; ?>
                    <?php \Widget\Themes\Rows::alloc()->to($themes); ?>
                    <?php while ($themes->next()): ?>
                        <tr id="theme-<?php $themes->name(); ?>"
                            class="<?php if ($themes->activated && !$options->missingTheme): ?>current<?php endif; ?>">
                            <td valign="top"><img src="<?php $themes->screen(); ?>"
                                                  alt="<?php $themes->name(); ?>"/></td>
                            <td valign="top">
                                <h3><?php '' != $themes->title ? $themes->title() : $themes->name(); ?></h3>
                                <cite>
                                    <?php if ($themes->author): ?><?php _e('Author'); ?>: <?php if ($themes->homepage): ?><a href="<?php $themes->homepage() ?>"><?php endif; ?><?php $themes->author(); ?><?php if ($themes->homepage): ?></a><?php endif; ?> &nbsp;&nbsp;<?php endif; ?>
                                    <?php if ($themes->version): ?><?php _e('Version'); ?>: <?php $themes->version() ?><?php endif; ?>
                                </cite>
                                <p><?php echo nl2br($themes->description); ?></p>
                                <?php if ($options->theme != $themes->name || $options->missingTheme): ?>
                                    <p>
                                        <?php if (\Widget\Themes\Files::isWriteable()): ?>
                                            <a class="edit"
                                               href="<?php $options->adminUrl('theme-editor.php?theme=' . $themes->name); ?>"><?php _e('Editors'); ?></a> &nbsp;
                                        <?php endif; ?>
                                        <a class="activate"
                                           href="<?php $security->index('/action/themes-edit?change=' . $themes->name); ?>"><?php _e('Enable.'); ?></a>
                                    </p>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<?php
include 'copyright.php';
include 'common-js.php';
include 'footer.php';
?>
