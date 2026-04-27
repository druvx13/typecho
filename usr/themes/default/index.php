<?php
/**
 * Default theme for Typecho
 *
 * @package Typecho Replica Theme
 * @author Typecho Team
 * @version 1.2
 * @link http://typecho.org
 */

if (!defined('__TYPECHO_ROOT_DIR__')) exit;
$this->need('header.php');
?>

<div class="col-mb-12 col-8" id="main" role="main">
    <?php if (!($this->is('index')) && !($this->is('post'))): ?>
    <h3 class="archive-title"><?php $this->archiveTitle([
            'category' => _t('Categories %s 下的文章'),
            'search'   => _t('Posts containing keyword %s'),
            'tag'      => _t('Tags %s 下的文章'),
            'author'   => _t('Posts published by %s')
        ], '', ''); ?></h3>
    <?php endif; ?>
    <?php if ($this->have()): ?>
    <?php while ($this->next()): ?>
        <article class="post" itemscope itemtype="http://schema.org/BlogPosting">
            <?php postMeta($this); ?>
            <div class="post-content" itemprop="articleBody">
                <?php $this->content(_t('Read the rest')); ?>
            </div>
        </article>
    <?php endwhile; ?>
    <?php else: ?>
        <article class="post">
            <h2 class="post-title"><?php _e('No content found'); ?></h2>
        </article>
    <?php endif; ?>

    <?php $this->pageNav('&laquo; ' . _t('Previous'), _t('Next') . ' &raquo;'); ?>
</div><!-- end #main-->

<?php $this->need('sidebar.php'); ?>
<?php $this->need('footer.php'); ?>
