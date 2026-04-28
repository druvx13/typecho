<?php if(!defined('__TYPECHO_ADMIN__')) exit; ?>
<footer class="typecho-foot" role="contentinfo">
    <div class="copyright">
        <a href="https://typecho.org" class="i-logo-s">Typecho</a>
        <p><?php _e('Powered by <a href="https://typecho.org">%s</a>, version %s', $options->software, $options->version); ?></p>
    </div>
    <nav class="resource">
        <a href="https://docs.typecho.org"><?php _e('Help'); ?></a> &bull;
        <a href="https://forum.typecho.org"><?php _e('Support'); ?></a> &bull;
        <a href="https://github.com/typecho/typecho/issues"><?php _e('Report bugs'); ?></a> &bull;
        <a href="https://typecho.org/download"><?php _e('Download'); ?></a>
    </nav>
</footer>
