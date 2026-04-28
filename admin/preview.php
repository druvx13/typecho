<?php

include 'common.php';

/** Retrieve content Widget */
\Widget\Archive::alloc('type=single&checkPermalink=0&preview=1')->to($content);

/** Check whether content exists */
if (!$content->have()) {
    $response->redirect($options->adminUrl);
}

/** Check permissions */
if (!$user->pass('editor', true) && $content->authorId != $user->uid) {
    $response->redirect($options->adminUrl);
}

/** Output content */
$content->render();
?>
<script>
    window.onbeforeunload = function () {
        if (!!window.parent) {
            window.parent.postMessage('cancelPreview', '<?php $options->rootUrl(); ?>');
        }
    }
</script>
