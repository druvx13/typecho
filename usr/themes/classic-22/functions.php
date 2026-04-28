<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

function themeConfig($form)
{
    $logoUrl = new \Typecho\Widget\Helper\Form\Element\Text(
        'logoUrl',
        null,
        null,
        _t('Website Logo'),
        _t('Enter an image URL here to display the site logo')
    );

    $form->addInput($logoUrl->addRule('url', _t('Please enter a valid URL')));

    $colorSchema = new \Typecho\Widget\Helper\Form\Element\Select(
        'colorSchema',
        array(
            null => _t('Auto'),
            'light' => _t('Light'),
            'dark' => _t('Dark'),
            'customize' => _t('Custom'),
        ),
        null,
        _t('Appearance'),
        _t('If Custom is selected, the theme will use styles from theme.css')
    );

    $form->addInput($colorSchema);
}

function postMeta(
    \Widget\Archive $archive,
    string $metaType = 'archive'
)
{
?>
    <header class="entry-header text-center">
        <h1 class="entry-title" itemprop="name headline">
            <a href="<?php $archive->permalink() ?>" itemprop="url"><?php $archive->title() ?></a>
        </h1>
        <?php if ($metaType != 'page'): ?>
        <ul class="entry-meta list-inline text-muted">
            <li class="feather-calendar"><time datetime="<?php $archive->date('c'); ?>" itemprop="datePublished"><?php $archive->date(); ?></time></li>
            <li class="feather-folder"><?php $archive->category(', '); ?></li>
            <li class="feather-message"><a href="<?php $archive->permalink() ?>#comments"  itemprop="discussionUrl"><?php $archive->commentsNum(_t('No comments yet'), _t('1 comment'), _t('%d comments')); ?></a></li>
        </ul>
        <?php endif; ?>
    </header>
<?php
}
