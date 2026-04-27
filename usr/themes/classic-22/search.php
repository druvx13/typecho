<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php $this->need('header.php'); ?>

<main class="container">
    <div class="container-thin">

        <h1 class="text-center"><?php _e('Search'); ?></h1>
        
        <form method="post" action="<?php $this->options->siteUrl(); ?>">
            <input type="search" id="s" name="s" placeholder="<?php _e('Search keywords'); ?>" value="<?php $this->archiveTitle('','',''); ?>">
        </form>

        <div class="text-center">
            <?php \Widget\Metas\Category\Rows::alloc()->listCategories('wrapClass=list-inline'); ?>
        </div>
    
        <hr class="post-separator">

    <?php if ($this->have()): ?>
        <?php while ($this->next()): ?>
        <article class="post" itemscope itemtype="http://schema.org/BlogPosting">
            <?php postMeta($this); ?>
            
            <div class="entry-content fmt" itemprop="articleBody">
                <?php $this->content('Read more'); ?>
            </div>
        </article>
        <hr class="post-separator">
        <?php endwhile; ?>
    <?php else: ?>
        <article class="post">
            <div class="entry-content fmt text-center" itemprop="articleBody">
                <p><?php _e('No content found'); ?></p>
            </div>
        </article>
    <?php endif; ?>
    </div>

    <?php $this->pageNav('&laquo; Previous', 'Next &raquo;'); ?>
</main>

<?php $this->need('footer.php'); ?>
