<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php $this->need('header.php'); ?>

<div class="col-mb-12 col-8" id="main" role="main">
    <article class="post" itemscope itemtype="http://schema.org/BlogPosting">
        <?php postMeta($this, 'post'); ?>
        <div class="post-content" itemprop="articleBody">
            <?php $this->content(); ?>
        </div>
        <p itemprop="keywords" class="tags"><?php _e('Tags'); ?>: <?php $this->tags(', ', true, 'none'); ?></p>
    </article>

    <?php $this->need('comments.php'); ?>

    <ul class="post-near">
        <li>Previous Post: <?php $this->thePrev('%s', _t('None')); ?></li>
        <li>Next Post: <?php $this->theNext('%s', _t('None')); ?></li>
    </ul>
</div><!-- end #main-->

<?php $this->need('sidebar.php'); ?>
<?php $this->need('footer.php'); ?>
