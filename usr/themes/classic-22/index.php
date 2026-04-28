<?php
/**
 * Just another official theme
 *
 * @package Classic 22
 * @author Typecho Team
 * @version 1.0
 * @link http://typecho.org
 */

if (!defined('__TYPECHO_ROOT_DIR__')) exit;
$this->need('header.php');
?>

<main class="container">
    <div class="container-thin">
        <?php if (!($this->is('index')) && !($this->is('post'))): ?>
            <h6 class="text-center text-muted">
                <?php $this->archiveTitle([
                    'category' => _t('Posts in category %s'),
                    'search'   => _t('Posts containing keyword %s'),
                    'tag'      => _t('Posts tagged %s'),
                    'author'   => _t('Posts published by %s')
                ], '', ''); ?>
            </h6>
        <?php endif; ?>

        <?php while ($this->next()): ?>
            <article class="post" itemscope itemtype="http://schema.org/BlogPosting">
                <?php postMeta($this); ?>
                
                <div class="entry-content fmt" itemprop="articleBody">
                    <?php $this->content(_t('Read more')); ?>
                </div>
            </article>
            <hr class="post-separator">
        <?php endwhile; ?>

        <nav><?php $this->pageNav(_t('Previous'), _t('Next'), 2, '...', array('wrapTag' => 'ul', 'itemTag' => 'li')); ?></nav>
    </div>

</main>

<?php $this->need('footer.php'); ?>
