<?php if(!defined('__TYPECHO_ADMIN__')) exit; ?>
<?php $content = !empty($post) ? $post : $page; ?>
<script>
(function () {
    $('#text').on('change', function (e) {
        e.preventDefault();
        e.stopPropagation();
    }).on('input', function () {
        $(this).parents('form').trigger('write');
    });
})();
</script>
<?php if (!$options->markdown): ?>
<script>
(function () {
    const textarea = $('#text');

    // Original image/file insertion
    Typecho.insertFileToEditor = function (file, url, isImage) {
        const sel = textarea.getSelection(),
            html = isImage ? '<img src="' + url + '" alt="' + file + '" />'
                : '<a href="' + url + '">' + file + '</a>',
            offset = (sel ? sel.start : 0) + html.length;

        textarea.replaceSelection(html);
        textarea.setSelection(offset, offset);
    };
})();
</script>
<?php else: ?>
<script src="<?php $options->adminStaticUrl('js', 'hyperdown.js'); ?>"></script>
<script src="<?php $options->adminStaticUrl('js', 'pagedown.js'); ?>"></script>
<script src="<?php $options->adminStaticUrl('js', 'purify.js'); ?>"></script>
<script>
$(document).ready(function () {
    const textarea = $('#text'),
        toolbar = $('<div class="editor" id="wmd-button-bar" />').insertBefore(textarea.parent()),
        preview = $('<div id="wmd-preview" class="wmd-hidetab" />').insertAfter('.editor');
    let isFullScreen = false;

    const options = {}, isMarkdown = <?php echo json_encode(!$content->have() || $content->isMarkdown); ?>;

    options.strings = {
        bold: '<?php _e('Bold'); ?> <strong> Ctrl+B',
        boldexample: '<?php _e('Bold text'); ?>',
            
        italic: '<?php _e('Italic'); ?> <em> Ctrl+I',
        italicexample: '<?php _e('Italic text'); ?>',

        link: '<?php _e('Link'); ?> <a> Ctrl+L',
        linkdescription: '<?php _e('Please enter link description.'); ?>',

        quote:  '<?php _e('Cite'); ?> <blockquote> Ctrl+Q',
        quoteexample: '<?php _e('Cite text'); ?>',

        code: '<?php _e('Code'); ?> <pre><code> Ctrl+K',
        codeexample: '<?php _e('Please insert code.'); ?>',

        image: '<?php _e('Image'); ?> <img> Ctrl+G',
        imagedescription: '<?php _e('Please enter image description.'); ?>',

        olist: '<?php _e('Numeric lists'); ?> <ol> Ctrl+O',
        ulist: '<?php _e('Unordered list'); ?> <ul> Ctrl+U',
        litem: '<?php _e('List items'); ?>',

        heading: '<?php _e('Title'); ?> <h1>/<h2> Ctrl+H',
        headingexample: '<?php _e('Title text'); ?>',

        hr: '<?php _e('Separator'); ?> <hr> Ctrl+R',
        more: '<?php _e('Abstract separator'); ?> <!--more--> Ctrl+M',

        undo: '<?php _e('Undo'); ?> - Ctrl+Z',
        redo: '<?php _e('Redo'); ?> - Ctrl+Y',
        redomac: '<?php _e('Redo'); ?> - Ctrl+Shift+Z',

        fullscreen: '<?php _e('Fullscreen'); ?> - Ctrl+J',
        exitFullscreen: '<?php _e('Quit fullscreen'); ?> - Ctrl+E',
        fullscreenUnsupport: '<?php _e('Your browser does not support fullscreen'); ?>',

        imagedialog: '<p><b><?php _e('Insert images.'); ?></b></p><p><?php _e('Please enter the image URL:'); ?></p><p><?php _e('You can also use a locally uploaded picture with the attachment feature'); ?></p>',
        linkdialog: '<p><b><?php _e('Insert a link.'); ?></b></p><p><?php _e('Please enter the link URL:'); ?></p>',

        ok: '<?php _e('OK'); ?>',
        cancel: '<?php _e('Cancel'); ?>',

        help: '<?php _e('Markdown syntax help'); ?>'
    };

    const converter = new HyperDown(),
        editor = new Markdown.Editor(converter, '', options);

    // Auto-follow
    converter.enableHtml(true);
    converter.enableLine(true);
    const reloadScroll = scrollableEditor(textarea, preview);

    // Fix allowlist
    converter.hook('makeHtml', function (html) {
        html = html.replace('<p><!--more--></p>', '<!--more-->');
        
        if (html.indexOf('<!--more-->') > 0) {
            var parts = html.split(/\s*<\!\-\-more\-\->\s*/),
                summary = parts.shift(),
                details = parts.join('');

            html = '<div class="summary">' + summary + '</div>'
                + '<div class="details">' + details + '</div>';
        }

        // Replace block
        html = html.replace(/<(iframe|embed)\s+([^>]*)>/ig, function (all, tag, src) {
            if (src[src.length - 1] === '/') {
                src = src.substring(0, src.length - 1);
            }

            return '<div class="embed"><strong>'
                + tag + '</strong> : ' + $.trim(src) + '</div>';
        });

        return DOMPurify.sanitize(html, {USE_PROFILES: {html: true}});
    });

    editor.hooks.chain('onPreviewRefresh', function () {
        const images = $('img', preview);
        let count = images.length;

        if (count === 0) {
            reloadScroll(true);
        } else {
            images.bind('load error', function () {
                count --;

                if (count === 0) {
                    reloadScroll(true);
                }
            });
        }
    });

    <?php \Typecho\Plugin::factory('admin/editor-js.php')->call('markdownEditor', $content); ?>

    let th = textarea.height(), ph = preview.height();
    const uploadBtn = $('<button type="button" id="btn-fullscreen-upload" class="btn btn-link">'
            + '<i class="i-upload"><?php _e('Attachments'); ?></i></button>')
            .prependTo('.submit .right')
            .click(function() {
                $('a', $('.typecho-option-tabs li').not('.active')).trigger('click');
                return false;
            });

    $('.typecho-option-tabs li').click(function () {
        uploadBtn.find('i').toggleClass('i-upload-active',
            $('#tab-files-btn', this).length > 0);
    });

    editor.hooks.chain('enterFakeFullScreen', function () {
        th = textarea.height();
        ph = preview.height();
        $(document.body).addClass('fullscreen');
        const h = $(window).height() - toolbar.outerHeight();
        
        textarea.css('height', h);
        preview.css('height', h);
        isFullScreen = true;
    });

    editor.hooks.chain('enterFullScreen', function () {
        $(document.body).addClass('fullscreen');
        
        const h = window.screen.height - toolbar.outerHeight();
        textarea.css('height', h);
        preview.css('height', h);
        isFullScreen = true;
    });

    editor.hooks.chain('exitFullScreen', function () {
        $(document.body).removeClass('fullscreen');
        textarea.height(th);
        preview.height(ph);
        isFullScreen = false;
    });

    editor.hooks.chain('commandExecuted', function () {
        textarea.trigger('input');
    });

    editor.hooks.chain('save', function () {
        Typecho.savePost();
    });

    function initMarkdown() {
        editor.run();

        const imageButton = $('#wmd-image-button'),
            linkButton = $('#wmd-link-button');

        Typecho.insertFileToEditor = function (file, url, isImage) {
            const button = isImage ? imageButton : linkButton;

            options.strings[isImage ? 'imagename' : 'linkname'] = file;
            button.trigger('click');

            let checkDialog = setInterval(function () {
                if ($('.wmd-prompt-dialog').length > 0) {
                    $('.wmd-prompt-dialog input').val(url).select();
                    clearInterval(checkDialog);
                    checkDialog = null;
                }
            }, 10);
        };

        Typecho.uploadComplete = function (attachment) {
            Typecho.insertFileToEditor(attachment.title, attachment.url, attachment.isImage);
        };

        // Edit/preview toggle
        const edittab = $('.editor').append('<div class="wmd-edittab"><a href="#wmd-editarea" class="active"><?php _e('Write'); ?></a><a href="#wmd-preview"><?php _e('Preview'); ?></a></div>'),
            editarea = $(textarea.parent()).attr("id", "wmd-editarea");

        $(".wmd-edittab a").click(function() {
            $(".wmd-edittab a").removeClass('active');
            $(this).addClass("active");
            $("#wmd-editarea, #wmd-preview").addClass("wmd-hidetab");
        
            const selected_tab = $(this).attr("href"),
                selected_el = $(selected_tab).removeClass("wmd-hidetab");

            // Hide editor buttons in preview mode
            if (selected_tab === "#wmd-preview") {
                $("#wmd-button-row").addClass("wmd-visualhide");
            } else {
                $("#wmd-button-row").removeClass("wmd-visualhide");
            }

            // Match preview and editor window heights
            $("#wmd-preview").outerHeight($("#wmd-editarea").innerHeight());

            return false;
        });

        // Paste image from clipboard
        textarea.bind('paste', function (e) {
            const items = (e.clipboardData || e.originalEvent.clipboardData).items;

            for (const item of items) {
                if (item.kind === 'file') {
                    const file = item.getAsFile();

                    if (file.size > 0) {
                        if (!file.name) {
                            file.name = (new Date()).toISOString().replace(/\..+$/, '')
                                + '.' + file.type.split('/').pop();
                        }

                        Typecho.uploadFile(file);
                    }
                }
            }
        });
    }

    if (isMarkdown) {
        initMarkdown();
    } else {
        const notice = $('<div class="message notice"><?php _e('This article was not created with Markdown. Do you want to continue using Markdown to edit it?'); ?> '
            + '<button class="btn btn-xs primary yes"><?php _e('Is'); ?></button> ' 
            + '<button class="btn btn-xs no"><?php _e('Whether'); ?></button></div>')
            .hide().insertBefore(textarea).slideDown();

        $('.yes', notice).click(function () {
            notice.remove();
            $('<input type="hidden" name="markdown" value="1" />').appendTo('.submit');
            initMarkdown();
        });

        $('.no', notice).click(function () {
            notice.remove();
        });
    }
});
</script>
<?php endif; ?>

