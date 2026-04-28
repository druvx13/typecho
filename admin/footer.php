<?php if(!defined('__TYPECHO_ADMIN__')) exit; ?>
<?php \Typecho\Plugin::factory('admin/footer.php')->call('begin'); ?>
    </body>
</html>
<?php
/** Register a shutdown hook */
\Typecho\Plugin::factory('admin/footer.php')->call('end');
