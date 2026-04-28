<?php
include 'common.php';
include 'header.php';
include 'menu.php';

$page = \Widget\Contents\Page\Edit::alloc()->prepare();

$parentPageId = $page->getParent();
$parentPages = [0 => _t('Deselect')];
$parents = \Widget\Contents\Page\Admin::allocWithAlias(
    'options',
    'ignoreRequest=1' . ($request->is('cid') ? '&ignore=' . $request->get('cid') : '')
);

while ($parents->next()) {
    $parentPages[$parents->cid] = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $parents->levels) . $parents->title;
}
?>
<main class="main">
    <div class="body container">
        <?php include 'page-title.php'; ?>
        <form class="row typecho-page-main typecho-post-area" action="<?php $security->index('/action/contents-page-edit'); ?>" method="post" name="write_page">
            <div class="col-mb-12 col-tb-9" role="main">
                <?php if ($page->draft): ?>
                    <?php if ($page->draft['cid'] != $page->cid): ?>
                        <?php $pageModifyDate = new \Typecho\Date($page->draft['modified']); ?>
                        <cite
                            class="edit-draft-notice"><?php _e('You are editing a revision saved at %s. You can also <a href="%s">delete it</a>.', $pageModifyDate->word(),
                                $security->getIndex('/action/contents-page-edit?do=deleteDraft&cid=' . $page->cid)); ?></cite>
                    <?php else: ?>
                        <cite class="edit-draft-notice"><?php _e('Unpublished drafts currently being edited'); ?></cite>
                    <?php endif; ?>
                    <input name="draft" type="hidden" value="<?php echo $page->draft['cid'] ?>"/>
                <?php endif; ?>

                <p class="title">
                    <label for="title" class="sr-only"><?php _e('Title'); ?></label>
                    <input type="text" id="title" name="title" autocomplete="off" value="<?php $page->title(); ?>"
                           placeholder="<?php _e('Title'); ?>" class="w-100 text title"/>
                </p>
                <?php $permalink = \Typecho\Common::url($options->routingTable['page']['url'], $options->index);
                [$scheme, $permalink] = explode(':', $permalink, 2);
                $permalink = ltrim($permalink, '/');
                $permalink = preg_replace("/\[([_a-z0-9-]+)[^\]]*\]/i", "{\\1}", $permalink);
                if ($page->have()) {
                    $permalink = preg_replace_callback(
                        "/\{(cid)\}/i",
                        function ($matches) use ($page) {
                            $key = $matches[1];
                            return $page->getRouterParam($key);
                        },
                        $permalink
                    );
                }
                $input = '<input type="text" id="slug" name="slug" autocomplete="off" value="' . htmlspecialchars($page->slug ?? '') . '" class="mono" />';
                ?>
                <p class="mono url-slug">
                    <label for="slug" class="sr-only"><?php _e('URL abbreviation'); ?></label>
                    <?php echo preg_replace_callback("/\{(slug|directory)\}/i", function ($matches) use ($input) {
                        if ($matches[1] == 'slug') {
                            return $input;
                        } else {
                            return '{directory/' . $input . '}';
                        }
                    }, $permalink); ?>
                </p>
                <p>
                    <label for="text" class="sr-only"><?php _e('Page content'); ?></label>
                    <textarea style="height: <?php $options->editorSize(); ?>px" autocomplete="off" id="text"
                              name="text" class="w-100 mono"><?php echo htmlspecialchars($page->text); ?></textarea>
                </p>

                <?php include 'custom-fields.php'; ?>
                <p class="submit">
                    <span class="left">
                        <button type="button" id="btn-cancel-preview" class="btn"><i
                                class="i-caret-left"></i> <?php _e('Cancel Preview'); ?></button>
                    </span>
                    <span class="right">
                        <input type="hidden" name="do" value="publish" />
                        <input type="hidden" name="cid" value="<?php $page->cid(); ?>"/>
                        <button type="button" id="btn-preview" class="btn"><i
                                class="i-exlink"></i> <?php _e('Preview page'); ?></button>
                        <button type="submit" name="do" value="save" id="btn-save"
                                class="btn"><?php _e('Save draft'); ?></button>
                        <button type="submit" name="do" value="publish" class="btn primary"
                                id="btn-submit"><?php _e('Publish page'); ?></button>
                        <?php if ($options->markdown && (!$page->have() || $page->isMarkdown)): ?>
                            <input type="hidden" name="markdown" value="1"/>
                        <?php endif; ?>
                    </span>
                </p>

                <?php \Typecho\Plugin::factory('admin/write-page.php')->call('content', $page); ?>
            </div>
            <div id="edit-secondary" class="col-mb-12 col-tb-3" role="complementary">
                <ul class="typecho-option-tabs">
                    <li class="active w-50"><a href="#tab-advance"><?php _e('Options'); ?></a></li>
                    <li class="w-50"><a href="#tab-files" id="tab-files-btn"><?php _e('Attachments'); ?></a></li>
                </ul>

                <div id="tab-advance" class="tab-content">
                    <section class="typecho-post-option" role="application">
                        <label for="date" class="typecho-label"><?php _e('Publish date'); ?></label>
                        <p><input class="typecho-date w-100" type="text" name="date" id="date" autocomplete="off"
                                  value="<?php $page->have() && $page->created > 0 ? $page->date('Y-m-d H:i') : ''; ?>"/>
                        </p>
                    </section>

                    <section class="typecho-post-option">
                        <label for="order" class="typecho-label"><?php _e('Page order'); ?></label>
                        <p><input type="number" id="order" name="order" value="<?php $page->order(); ?>"
                                  class="w-100"/></p>
                        <p class="description"><?php _e('Set an index of your customized pages, so they are sortable from small index to big index.'); ?></p>
                    </section>

                    <section class="typecho-post-option">
                        <label for="template" class="typecho-label"><?php _e('Customize template'); ?></label>
                        <p>
                            <select name="template" id="template">
                                <option value=""><?php _e('Deselect'); ?></option>
                                <?php $templates = $page->getTemplates();
                                foreach ($templates as $template => $name): ?>
                                    <option
                                        value="<?php echo $template; ?>"<?php if ($template == $page->template): ?> selected="true"<?php endif; ?>><?php echo $name; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </p>
                        <p class="description"><?php _e('If you select a custom template for this page, the system will use the selected template file to display it.'); ?></p>
                    </section>

                    <section class="typecho-post-option">
                        <label for="parent" class="typecho-label"><?php _e('Parent page'); ?></label>
                        <p>
                            <select name="parent" id="parent">
                                <?php foreach ($parentPages as $pageId => $pageTitle): ?>
                                    <option
                                        value="<?php echo $pageId; ?>"<?php if ($pageId == ($page->parent ?? $parentPageId)): ?> selected="true"<?php endif; ?>><?php echo $pageTitle; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </p>
                        <p class="description"><?php _e('If you set a parent page, this page will appear as a sub-page.'); ?></p>
                    </section>

                    <?php \Typecho\Plugin::factory('admin/write-page.php')->call('option', $page); ?>

                    <details id="advance-panel">
                        <summary class="btn btn-xs"><?php _e('Advance options'); ?> <i class="i-caret-down"></i></summary>

                        <section class="typecho-post-option visibility-option">
                            <label for="visibility" class="typecho-label"><?php _e('Publicity'); ?></label>
                            <p>
                                <select id="visibility" name="visibility">
                                    <option
                                        value="publish"<?php if ($page->status == 'publish' || !$page->status): ?> selected<?php endif; ?>><?php _e('Public'); ?></option>
                                    <option
                                        value="hidden"<?php if ($page->status == 'hidden'): ?> selected<?php endif; ?>><?php _e('Hide'); ?></option>
                                </select>
                            </p>
                        </section>

                        <section class="typecho-post-option allow-option">
                            <label class="typecho-label"><?php _e('Permissions'); ?></label>
                            <ul>
                                <li><input id="allowComment" name="allowComment" type="checkbox" value="1"
                                           <?php if ($page->allow('comment')): ?>checked="true"<?php endif; ?> />
                                    <label for="allowComment"><?php _e('Allow comments'); ?></label></li>
                                <li><input id="allowPing" name="allowPing" type="checkbox" value="1"
                                           <?php if ($page->allow('ping')): ?>checked="true"<?php endif; ?> />
                                    <label for="allowPing"><?php _e('Allow cited'); ?></label></li>
                                <li><input id="allowFeed" name="allowFeed" type="checkbox" value="1"
                                           <?php if ($page->allow('feed')): ?>checked="true"<?php endif; ?> />
                                    <label for="allowFeed"><?php _e('Allow aggregate'); ?></label></li>
                            </ul>
                        </section>

                        <?php \Typecho\Plugin::factory('admin/write-page.php')->call('advanceOption', $page); ?>
                    </details>
                    <?php if ($page->have()): ?>
                        <?php $modified = new \Typecho\Date($page->modified); ?>
                        <section class="typecho-post-option">
                            <p class="description">
                                <br>&mdash;<br>
                                <?php _e('This page is created by <a href="%s">%s</a>.',
                                    \Typecho\Common::url('manage-pages.php?uid=' . $page->author->uid, $options->adminUrl), $page->author->screenName); ?>
                                <br>
                                <?php _e(' Last updated at %s', $modified->word()); ?>
                            </p>
                        </section>
                    <?php endif; ?>
                </div><!-- end #tab-advance -->

                <div id="tab-files" class="tab-content hidden">
                    <?php include 'file-upload.php'; ?>
                </div><!-- end #tab-files -->
            </div>
        </form>
    </div>
</main>

<?php
include 'copyright.php';
include 'common-js.php';
include 'form-js.php';
include 'write-js.php';

\Typecho\Plugin::factory('admin/write-page.php')->trigger($plugged)->call('richEditor', $page);
if (!$plugged) {
    include 'editor-js.php';
}

include 'file-upload-js.php';
include 'custom-fields-js.php';
\Typecho\Plugin::factory('admin/write-page.php')->bottom($page);
include 'footer.php';
?>
