<?php
include 'common.php';
include 'header.php';
include 'menu.php';
?>
<main class="main">
    <div class="body container">
        <?php include 'page-title.php'; ?>
        <div class="row typecho-page-main" role="main">
            <div class="col-mb-12 typecho-list">
                <?php \Widget\Plugins\Rows::allocWithAlias('activated', 'activated=1')->to($activatedPlugins); ?>
                <?php if ($activatedPlugins->have() || !empty($activatedPlugins->activatedPlugins)): ?>
                    <h4 class="typecho-list-table-title"><?php _e('Enabled plugins'); ?></h4>
                    <table class="typecho-list-table">
                        <colgroup>
                            <col width="25%"/>
                            <col width="45%"/>
                            <col width="8%" class="kit-hidden-mb"/>
                            <col width="10%" class="kit-hidden-mb"/>
                            <col width=""/>
                        </colgroup>
                        <thead>
                        <tr>
                            <th><?php _e('Name'); ?></th>
                            <th><?php _e('Description'); ?></th>
                            <th class="kit-hidden-mb"><?php _e('Version'); ?></th>
                            <th class="kit-hidden-mb"><?php _e('Author'); ?></th>
                            <th><?php _e('Operations'); ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php while ($activatedPlugins->next()): ?>
                            <tr id="plugin-<?php $activatedPlugins->name(); ?>">
                                <td><?php $activatedPlugins->title(); ?>
                                    <?php if (!$activatedPlugins->dependence): ?>
                                        <i class="i-delete"
                                           title="<?php _e('%s cannot work under this version of typecho', $activatedPlugins->title); ?>"></i>
                                    <?php endif; ?>
                                </td>
                                <td><?php $activatedPlugins->description(); ?></td>
                                <td class="kit-hidden-mb"><?php $activatedPlugins->version(); ?></td>
                                <td class="kit-hidden-mb"><?php echo empty($activatedPlugins->homepage) ? $activatedPlugins->author : '<a href="' . $activatedPlugins->homepage
                                        . '">' . $activatedPlugins->author . '</a>'; ?></td>
                                <td>
                                    <?php if ($activatedPlugins->activate || $activatedPlugins->deactivate || $activatedPlugins->config || $activatedPlugins->personalConfig): ?>
                                        <?php if ($activatedPlugins->config): ?>
                                            <a href="<?php $options->adminUrl('options-plugin.php?config=' . $activatedPlugins->name); ?>"><?php _e('Settings'); ?></a>
                                            &bull;
                                        <?php endif; ?>
                                        <a lang="<?php _e('Are you sure to disable plugin %s ?', $activatedPlugins->name); ?>"
                                           href="<?php $security->index('/action/plugins-edit?deactivate=' . $activatedPlugins->name); ?>"><?php _e('Disable'); ?></a>
                                    <?php else: ?>
                                        <span class="important"><?php _e('Plug and play.'); ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>

                        <?php if (!empty($activatedPlugins->activatedPlugins)): ?>
                            <?php foreach ($activatedPlugins->activatedPlugins as $key => $val): ?>
                                <tr>
                                    <td><?php echo $key; ?></td>
                                    <td colspan="3"><span
                                            class="warning"><?php _e('The files of this plugin has been damaged or removed unsafely. Disabling it is strongly recommended.'); ?></span></td>
                                    <td><a lang="<?php _e('Are you sure to disable plugin %s ?', $key); ?>"
                                           href="<?php $security->index('/action/plugins-edit?deactivate=' . $key); ?>"><?php _e('Disable'); ?></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        </tbody>
                    </table>
                <?php endif; ?>

                <?php \Widget\Plugins\Rows::allocWithAlias('unactivated', 'activated=0')->to($deactivatedPlugins); ?>
                <?php if ($deactivatedPlugins->have() || !$activatedPlugins->have()): ?>
                    <h4 class="typecho-list-table-title"><?php _e('Disabled plugins.'); ?></h4>
                    <table class="typecho-list-table deactivate">
                        <colgroup>
                            <col width="25%"/>
                            <col width="45%"/>
                            <col width="8%" class="kit-hidden-mb"/>
                            <col width="10%" class="kit-hidden-mb"/>
                            <col width=""/>
                        </colgroup>
                        <thead>
                        <tr>
                            <th><?php _e('Name'); ?></th>
                            <th><?php _e('Description'); ?></th>
                            <th class="kit-hidden-mb"><?php _e('Version'); ?></th>
                            <th class="kit-hidden-mb"><?php _e('Author'); ?></th>
                            <th class="typecho-radius-topright"><?php _e('Operations'); ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if ($deactivatedPlugins->have()): ?>
                            <?php while ($deactivatedPlugins->next()): ?>
                                <tr id="plugin-<?php $deactivatedPlugins->name(); ?>">
                                    <td><?php $deactivatedPlugins->title(); ?></td>
                                    <td><?php $deactivatedPlugins->description(); ?></td>
                                    <td class="kit-hidden-mb"><?php $deactivatedPlugins->version(); ?></td>
                                    <td class="kit-hidden-mb"><?php echo empty($deactivatedPlugins->homepage) ? $deactivatedPlugins->author : '<a href="' . $deactivatedPlugins->homepage
                                            . '">' . $deactivatedPlugins->author . '</a>'; ?></td>
                                    <td>
                                        <a href="<?php $security->index('/action/plugins-edit?activate=' . $deactivatedPlugins->name); ?>"><?php _e('Enable.'); ?></a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5"><h6 class="typecho-list-table-title"><?php _e('Uninstalled plugins.'); ?></h6>
                                </td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                <?php endif; ?>

            </div>
        </div>
    </div>
</main>

<?php
include 'copyright.php';
include 'common-js.php';
include 'footer.php';
?>
