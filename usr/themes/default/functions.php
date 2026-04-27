<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

function themeConfig($form)
{
    $logoUrl = new \Typecho\Widget\Helper\Form\Element\Text(
        'logoUrl',
        null,
        null,
        _t('Site Logo URL'),
        _t('Enter an image URL here to show a logo before the site title')
    );

    $form->addInput($logoUrl->addRule('url', _t('Please enter a valid URL')));

    $sidebarBlock = new \Typecho\Widget\Helper\Form\Element\Checkbox(
        'sidebarBlock',
        [
            'ShowRecentPosts'    => _t('Show recent posts'),
            'ShowRecentComments' => _t('Show recent comments'),
            'ShowCategory'       => _t('Show categories'),
            'ShowArchive'        => _t('Show archives'),
            'ShowOther'          => _t('Show other widgets')
        ],
        ['ShowRecentPosts', 'ShowRecentComments', 'ShowCategory', 'ShowArchive', 'ShowOther'],
        _t('Sidebar sections')
    );

    $form->addInput($sidebarBlock->multiMode());
}

function postMeta(
    \Widget\Archive $archive,
    string $metaType = 'archive'
)
{
    $titleTag = $metaType == 'archive' ? 'h2' : 'h1';
?>
    <<?php echo $titleTag ?> class="post-title" itemprop="name headline">
        <a itemprop="url"
           href="<?php $archive->permalink() ?>"><?php $archive->title() ?></a>
    </<?php echo $titleTag ?>>
    <?php if ($metaType != 'page'): ?>
        <ul class="post-meta">
            <li itemprop="author" itemscope itemtype="http://schema.org/Person">
                <?php _e('Author'); ?>: <a itemprop="name"
                                       href="<?php $archive->author->permalink(); ?>"
                                       rel="author"><?php $archive->author(); ?></a>
            </li>
            <li><?php _e('Date'); ?>:
                <time datetime="<?php $archive->date('c'); ?>" itemprop="datePublished"><?php $archive->date(); ?></time>
            </li>
            <li><?php _e('Categories'); ?>: <?php $archive->category(','); ?></li>
            <?php if ($metaType == 'archive'): ?>
                <li itemprop="interactionCount">
                    <a itemprop="discussionUrl"
                       href="<?php $archive->permalink() ?>#comments"><?php $archive->commentsNum(_t('No comments yet'), _t('1 comment'), _t('%d comments')); ?></a>
                </li>
            <?php endif; ?>
        </ul>
    <?php endif; ?>
<?php
}

/*
function themeFields($layout)
{
    $logoUrl = new \Typecho\Widget\Helper\Form\Element\Text(
        'logoUrl',
        null,
        null,
        _t('Site Logo URL'),
        _t('Enter an image URL here to show a logo before the site title')
    );
    $layout->addItem($logoUrl);
}
*/
