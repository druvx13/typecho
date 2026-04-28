<?php
include 'common.php';
include 'header.php';
include 'menu.php';

\Widget\Metas\Tag\Admin::alloc()->to($tags);
?>

<main class="main">
    <div class="body container">
        <?php include 'page-title.php'; ?>
        <div class="row typecho-page-main manage-metas">

            <div class="col-mb-12 col-tb-8" role="main">

                <form method="post" name="manage_tags" class="operate-form">
                    <div class="typecho-list-operate">
                        <div class="operate">
                            <label><i class="sr-only"><?php _e('Select all'); ?></i><input type="checkbox"
                                                                                   class="typecho-table-select-all"/></label>
                            <div class="btn-group btn-drop">
                                <button class="btn dropdown-toggle btn-s" type="button"><i
                                        class="sr-only"><?php _e('Operations'); ?></i><?php _e('Selected'); ?> <i
                                        class="i-caret-down"></i></button>
                                <ul class="dropdown-menu">
                                    <li><a lang="<?php _e('Delete these tags?'); ?>"
                                           href="<?php $security->index('/action/metas-tag-edit?do=delete'); ?>"><?php _e('Delete'); ?></a>
                                    </li>
                                    <li><a lang="<?php _e('Refresh these tags may take a long time, are you sure?'); ?>"
                                           href="<?php $security->index('/action/metas-tag-edit?do=refresh'); ?>"><?php _e('Refresh'); ?></a>
                                    </li>
                                    <li class="multiline">
                                        <button type="button" class="btn btn-s merge"
                                                rel="<?php $security->index('/action/metas-tag-edit?do=merge'); ?>"><?php _e('Combine to'); ?></button>
                                        <input type="text" name="merge" class="text-s"/>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <?php if ($tags->have()): ?>
                        <ul class="typecho-list-notable tag-list">
                            <?php while ($tags->next()): ?>
                                <li class="size-<?php $tags->split(5, 10, 20, 30); ?>" id="<?php $tags->theId(); ?>">
                                    <input type="checkbox" value="<?php $tags->mid(); ?>" name="mid[]"/>
                                    <span
                                        rel="<?php echo $request->makeUriByRequest('mid=' . $tags->mid); ?>"><?php $tags->name(); ?></span>
                                    <a class="tag-edit-link"
                                       href="<?php echo $request->makeUriByRequest('mid=' . $tags->mid); ?>"><i
                                            class="i-edit"></i></a>
                                </li>
                            <?php endwhile; ?>
                        </ul>
                    <?php else: ?>
                        <ul class="tag-list">
                            <li class="none"><?php _e('No tag.'); ?></li>
                        </ul>
                    <?php endif; ?>
                    <input type="hidden" name="do" value="delete"/>
                </form>

            </div>
            <div class="col-mb-12 col-tb-4" role="form">
                <?php \Widget\Metas\Tag\Edit::alloc()->form()->render(); ?>
            </div>
        </div>
    </div>
</main>

<?php
include 'copyright.php';
include 'common-js.php';
?>

<script type="text/javascript">
    (function () {
        $(document).ready(function () {

            $('.typecho-list-notable').tableSelectable({
                checkEl: 'input[type=checkbox]',
                rowEl: 'li',
                selectAllEl: '.typecho-table-select-all',
                actionEl: '.dropdown-menu a'
            });

            $('.btn-drop').dropdownMenu({
                btnEl: '.dropdown-toggle',
                menuEl: '.dropdown-menu'
            });

            $('.dropdown-menu button.merge').click(function () {
                var btn = $(this);
                btn.parents('form').attr('action', btn.attr('rel')).submit();
            });

            <?php if (isset($request->mid)): ?>
            $('.typecho-mini-panel').effect('highlight', '#AACB36');
            <?php endif; ?>
        });
    })();
</script>
<?php include 'footer.php'; ?>

