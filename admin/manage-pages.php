<?php
include 'common.php';
include 'header.php';
include 'menu.php';

$stat = \Widget\Stat::alloc();
$pages = \Widget\Contents\Page\Admin::alloc();
?>
<main class="main">
    <div class="body container">
        <?php include 'page-title.php'; ?>
        <div class="row typecho-page-main" role="main">
            <div class="col-mb-12 typecho-list">
                <form method="get" class="typecho-list-operate">
                    <div class="operate">
                        <label><i class="sr-only"><?php _e('Select all'); ?></i><input type="checkbox"
                                                                               class="typecho-table-select-all"/></label>
                        <div class="btn-group btn-drop">
                            <button class="btn dropdown-toggle btn-s" type="button"><i
                                    class="sr-only"><?php _e('Operations'); ?></i><?php _e('Selected'); ?> <i
                                    class="i-caret-down"></i></button>
                            <ul class="dropdown-menu">
                                <li><a lang="<?php _e('Are you sure to delete thiese pages?'); ?>"
                                       href="<?php $security->index('/action/contents-page-edit?do=delete'); ?>"><?php _e('Delete'); ?></a>
                                </li>
                                <li>
                                    <a href="<?php $security->index('/action/contents-page-edit?do=mark&status=publish'); ?>"><?php _e('Mark as: <strong>%s</strong>', _t('Public')); ?></a>
                                </li>
                                <li>
                                    <a href="<?php $security->index('/action/contents-page-edit?do=mark&status=hidden'); ?>"><?php _e('Mark as: <strong>%s</strong>', _t('Hide')); ?></a>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class="search" role="search">
                        <?php $pages->backLink(); ?>
                        <?php if ('' != $request->keywords): ?>
                            <a href="<?php $options->adminUrl('manage-pages.php'); ?>"><?php _e('&laquo; cancel the filtering'); ?></a>
                        <?php endif; ?>
                        <input type="text" class="text-s" placeholder="<?php _e('Please enter keywords'); ?>"
                               value="<?php echo $request->filter('html')->keywords; ?>" name="keywords"/>
                        <button type="submit" class="btn btn-s"><?php _e('Filter'); ?></button>
                    </div>
                </form>

                <form method="post" name="manage_pages" class="operate-form">
                    <table class="typecho-list-table">
                        <colgroup>
                            <col width="3%" class="kit-hidden-mb"/>
                            <col width="6%" class="kit-hidden-mb"/>
                            <col width="42%"/>
                            <col width="18%"/>
                            <col width="" class="kit-hidden-mb"/>
                            <col width="16%"/>
                        </colgroup>
                        <thead>
                        <tr class="nodrag">
                            <th class="kit-hidden-mb"></th>
                            <th class="kit-hidden-mb"></th>
                            <th><?php _e('Title'); ?></th>
                            <th><?php _e('Sub-pages'); ?></th>
                            <th class="kit-hidden-mb"><?php _e('Author'); ?></th>
                            <th><?php _e('Date'); ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if ($pages->have()): ?>
                            <?php while ($pages->next()): ?>
                                <tr id="<?php $pages->theId(); ?>">
                                    <td class="kit-hidden-mb"><input type="checkbox" value="<?php $pages->cid(); ?>"
                                                                     name="cid[]"/></td>
                                    <td class="kit-hidden-mb"><a
                                            href="<?php $options->adminUrl('manage-comments.php?cid=' . $pages->cid); ?>"
                                            class="balloon-button size-<?php echo \Typecho\Common::splitByCount($pages->commentsNum, 1, 10, 20, 50, 100); ?>"
                                            title="<?php $pages->commentsNum(); ?> <?php _e('Comments'); ?>"><?php $pages->commentsNum(); ?></a>
                                    </td>
                                    <td>
                                        <a href="<?php $options->adminUrl('write-page.php?cid=' . $pages->cid); ?>"><?php $pages->title(); ?></a>
                                        <?php
                                        if ('page_draft' == $pages->type) {
                                            echo '<em class="status">' . _t('Drafts') . '</em>';
                                        } elseif ($pages->revision) {
                                            echo '<em class="status">' . _t('Has revision') . '</em>';
                                        }

                                        if ('hidden' == $pages->status) {
                                            echo '<em class="status">' . _t('Hide') . '</em>';
                                        }
                                        ?>
                                        <a href="<?php $options->adminUrl('write-page.php?cid=' . $pages->cid); ?>"
                                           title="<?php _e('Edit %s', htmlspecialchars($pages->title)); ?>"><i
                                                class="i-edit"></i></a>
                                        <?php if ('page_draft' != $pages->type): ?>
                                            <a href="<?php $pages->permalink(); ?>"
                                               title="<?php _e('View %s', htmlspecialchars($pages->title)); ?>"><i
                                                    class="i-exlink"></i></a>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (count($pages->children) > 0): ?>
                                            <a href="<?php $options->adminUrl('manage-pages.php?parent=' . $pages->cid); ?>"><?php echo _n('one page', '%d pages', count($pages->children)); ?></a>
                                        <?php else: ?>
                                            <a href="<?php $options->adminUrl('write-page.php?parent=' . $pages->cid); ?>"><?php echo _e('Add'); ?></a>
                                        <?php endif; ?>
                                    </td>
                                    <td class="kit-hidden-mb"><?php $pages->author(); ?></td>
                                    <td>
                                        <?php if ('page_draft' == $pages->type || $pages->revision): ?>
                                            <span class="description">
                            <?php $modifyDate = new \Typecho\Date($pages->revision ? $pages->revision['modified'] : $pages->modified); ?>
                            <?php _e('Saved  at %s', $modifyDate->word()); ?>
                            </span>
                                        <?php else: ?>
                                            <?php $pages->dateWord(); ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="none"><?php _e('No page.'); ?></td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </form><!-- end .operate-form -->
            </div><!-- end .typecho-list -->
        </div><!-- end .typecho-page-main -->
    </div>
</main>

<?php
include 'copyright.php';
include 'common-js.php';
include 'table-js.php';
?>

<?php if (!$request->is('keywords')): ?>
    <script type="text/javascript">
        (function () {
            $(document).ready(function () {
                var table = $('.typecho-list-table').tableDnD({
                    onDrop: function () {
                        var ids = [];

                        $('input[type=checkbox]', table).each(function () {
                            ids.push($(this).val());
                        });

                        $.post('<?php $security->index('/action/contents-page-edit?do=sort'); ?>',
                            $.param({cid: ids}));
                    }
                });
            });
        })();
    </script>
<?php endif; ?>

<?php include 'footer.php'; ?>
