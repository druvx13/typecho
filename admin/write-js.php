<?php if(!defined('__TYPECHO_ADMIN__')) exit; ?>
<?php \Typecho\Plugin::factory('admin/write-js.php')->call('write'); ?>
<?php \Widget\Metas\Tag\Cloud::alloc('sort=count&desc=1&limit=200')->to($tags); ?>

<script src="<?php $options->adminStaticUrl('js', 'timepicker.js'); ?>"></script>
<script src="<?php $options->adminStaticUrl('js', 'tokeninput.js'); ?>"></script>
<script>
$(document).ready(function() {
    // Date-time widget
    $('#date').mask('9999-99-99 99:99').datetimepicker({
        currentText     :   '<?php _e('Now'); ?>',
        prevText        :   '<?php _e('Last month'); ?>',
        nextText        :   '<?php _e('Next month'); ?>',
        monthNames      :   ['<?php _e('Jun'); ?>', '<?php _e('Feb'); ?>', '<?php _e('Mar'); ?>', '<?php _e('Apr'); ?>',
            '<?php _e('May'); ?>', '<?php _e('Jun'); ?>', '<?php _e('Jul'); ?>', '<?php _e('Aug'); ?>',
            '<?php _e('Sept'); ?>', '<?php _e('Oct'); ?>', '<?php _e('Nov'); ?>', '<?php _e('Dec'); ?>'],
        dayNames        :   ['<?php _e('Sun'); ?>', '<?php _e('Mon'); ?>', '<?php _e('Tue'); ?>',
            '<?php _e('Wed'); ?>', '<?php _e('Thu'); ?>', '<?php _e('Fri'); ?>', '<?php _e('Sat'); ?>'],
        dayNamesShort   :   ['<?php _e('Sun'); ?>', '<?php _e('Mon'); ?>', '<?php _e('Tue'); ?>', '<?php _e('Wed'); ?>',
            '<?php _e('Thu'); ?>', '<?php _e('Fri'); ?>', '<?php _e('Sat'); ?>'],
        dayNamesMin     :   ['<?php _e('Sun'); ?>', '<?php _e('Mon'); ?>', '<?php _e('Tue'); ?>', '<?php _e('Wed'); ?>',
            '<?php _e('Thu'); ?>', '<?php _e('Fri'); ?>', '<?php _e('S'); ?>'],
        closeText       :   '<?php _e('Done'); ?>',
        timeOnlyTitle   :   '<?php _e('Select time'); ?>',
        timeText        :   '<?php _e('Time'); ?>',
        hourText        :   '<?php _e('hour'); ?>',
        amNames         :   ['<?php _e('a.m.'); ?>', 'A'],
        pmNames         :   ['<?php _e('p.m.'); ?>', 'P'],
        minuteText      :   '<?php _e('min'); ?>',
        secondText      :   '<?php _e('sec'); ?>',

        dateFormat      :   'yy-mm-dd',
        timezone        :   <?php $options->timezone(); ?> / 60,
        hour            :   (new Date()).getHours(),
        minute          :   (new Date()).getMinutes()
    });

    // Focus
    $('#title').select();

    // Auto-resize textarea
    Typecho.editorResize('text', '<?php $security->index('/action/ajax?do=editorResize'); ?>');

    // Tag autocomplete
    const tags = $('#tags'), tagsPre = [];
    
    if (tags.length > 0) {
        const items = tags.val().split(',');
        for (let i = 0; i < items.length; i ++) {
            const tag = items[i];

            if (!tag) {
                continue;
            }

            tagsPre.push({
                id      :   tag,
                tags    :   tag
            });
        }

        tags.tokenInput(<?php 
        $data = array();
        while ($tags->next()) {
            $data[] = array(
                'id'    =>  $tags->name,
                'tags'  =>  $tags->name
            );
        }
        echo json_encode($data);
        ?>, {
            propertyToSearch:   'tags',
            tokenValue      :   'tags',
            searchDelay     :   0,
            preventDuplicates   :   true,
            animateDropdown :   false,
            hintText        :   '<?php _e('Please enter tag name'); ?>',
            noResultsText   :   '<?php _e('This tag does not exist. Press enter to create it.'); ?>',
            prePopulate     :   tagsPre,

            onResult        :   function (result, query, val) {
                // remove special chars
                val = val.replace(/<|>|&|"|'/g, '');

                if (!query) {
                    return result;
                }

                if (!result) {
                    result = [];
                }

                if (!result[0] || result[0]['id'] !== query) {
                    result.unshift({
                        id      :   val,
                        tags    :   val
                    });
                }

                return result.slice(0, 5);
            }
        });

        // Tag autocomplete width
        $('#token-input-tags').focus(function() {
            const t = $('.token-input-dropdown'),
                offset = t.outerWidth() - t.width();
            t.width($('.token-input-list').outerWidth() - offset);
        });
    }

    // Adaptive width for slug field
    const slug = $('#slug');

    if (slug.length > 0) {
        const wrap = $('<div />').css({
            'position'  :   'relative',
            'display'   :   'inline-block'
        }),
        justifySlug = $('<pre />').css({
            'display'   :   'block',
            'visibility':   'hidden',
            'height'    :   slug.height(),
            'padding'   :   '0 2px',
            'margin'    :   0
        }).insertAfter(slug.wrap(wrap).css({
            'left'      :   0,
            'top'       :   0,
            'minWidth'  :   '5px',
            'position'  :   'absolute',
            'width'     :   '100%'
        }));

        function justifySlugWidth() {
            const val = slug.val();
            justifySlug.text(val.length > 0 ? val : '     ');
        }

        slug.bind('input propertychange', justifySlugWidth);
        justifySlugWidth();
    }

    // Handle post-save logic
    const form = $('form[name=write_post],form[name=write_page]'),
        idInput = $('input[name=cid]'),
        draft = $('input[name=draft]'),
        btnPreview = $('#btn-preview'),
        autoSave = $('<span id="auto-save-message"></span>').prependTo('.left');

    let cid = idInput.val(),
        draftId = draft.length > 0 ? draft.val() : 0,
        changed = false,
        written = false,
        lastSaveTime = null;

    form.on('write', function () {
        written = true;
        form.trigger('datachange');
    });

    form.on('change', function () {
        if (written) {
            form.trigger('datachange');
        }
    });

    $('button[name=do]').click(function () {
        $('input[name=do]').val($(this).val());
    });

    // Auto-detect page leave
    $(window).bind('beforeunload', function () {
        if (changed && !form.hasClass('submitting')) {
            return '<?php _e('Changes are not saved. Are you sure to leave this page?'); ?>';
        }
    });

    // Send save request
    Typecho.savePost = function(cb) {
        if (!changed) {
            cb && cb();
            return;
        }

        const callback = function (o) {
            lastSaveTime = o.time;
            cid = o.cid;
            draftId = o.draftId;
            idInput.val(cid);
            autoSave.text('<?php _e('Saved'); ?>' + ' (' + o.time + ')').effect('highlight', 1000);

            cb && cb();
        };

        changed = false;
        autoSave.text('<?php _e('Saving...'); ?>');

        const data = new FormData(form.get(0));
        data.append('do', 'save');
        form.triggerHandler('submit');

        $.ajax({
            url: form.attr('action'),
            processData: false,
            contentType: false,
            type: 'POST',
            data: data,
            success: callback,
            error: function () {
                autoSave.text('<?php _e('Save failed, please try again'); ?>');
            },
            complete: function () {
                form.trigger('submitted');
            }
        });
    };

    <?php if ($options->autoSave): ?>
    // Auto-save
    let saveTimer = null;
    let stopAutoSave = false;

    form.on('datachange', function () {
        changed = true;
        autoSave.text('<?php _e('Not saved'); ?>' + (lastSaveTime ? ' (<?php _e('Last save'); ?>: ' + lastSaveTime + ')' : ''));

        if (saveTimer) {
            clearTimeout(saveTimer);
        }

        saveTimer = setTimeout(function () {
            !stopAutoSave && Typecho.savePost();
        }, 3000);
    }).on('submit', function () {
        stopAutoSave = true;
    }).on('submitted', function () {
        stopAutoSave = false;
    });
    <?php else: ?>
    form.on('datachange', function () {
        changed = true;
    });
    <?php endif; ?>

    // Calculate daylight saving time offset
    const dstOffset = (function () {
        const d = new Date(),
            jan = new Date(d.getFullYear(), 0, 1),
            jul = new Date(d.getFullYear(), 6, 1),
            stdOffset = Math.max(jan.getTimezoneOffset(), jul.getTimezoneOffset());

        return stdOffset - d.getTimezoneOffset();
    })();
    
    if (dstOffset > 0) {
        $('<input name="dst" type="hidden" />').appendTo(form).val(dstOffset);
    }

    // Timezone
    $('<input name="timezone" type="hidden" />').appendTo(form).val(- (new Date).getTimezoneOffset() * 60);

    // Preview feature
    let isFullScreen = false;

    function previewData(cid) {
        isFullScreen = $(document.body).hasClass('fullscreen');
        $(document.body).addClass('fullscreen preview');

        const frame = $('<iframe frameborder="0" class="preview-frame preview-loading"></iframe>')
            .attr('src', './preview.php?cid=' + cid)
            .attr('sandbox', 'allow-same-origin allow-scripts')
            .appendTo(document.body);

        frame.load(function () {
            frame.removeClass('preview-loading');
        });

        frame.height($(window).height() - 53);
    }

    function cancelPreview() {
        if (!isFullScreen) {
            $(document.body).removeClass('fullscreen');
        }

        $(document.body).removeClass('preview');
        $('.preview-frame').remove();
    }

    $('#btn-cancel-preview').click(cancelPreview);

    $(window).bind('message', function (e) {
        if (e.originalEvent.data === 'cancelPreview') {
            cancelPreview();
        }
    });

    btnPreview.click(function () {
        if (changed) {
            if (confirm('<?php _e('To preview, you need to save the content. Continue?'); ?>')) {
                Typecho.savePost(function () {
                    previewData(draftId);
                });
            }
        } else if (!!draftId) {
            previewData(draftId);
        } else if (!!cid) {
            previewData(cid);
        }
    });

    // Control toggling between options and attachments
    $('#edit-secondary .typecho-option-tabs li').click(function() {
        $('#edit-secondary .typecho-option-tabs li.active').removeClass('active');
        $('#edit-secondary .tab-content').addClass('hidden');

        const activeTab = $(this).addClass('active').find('a').attr('href');
        $(activeTab).removeClass('hidden');

        return false;
    });

    // Auto-hide password field
    $('#visibility').change(function () {
        const val = $(this).val(), password = $('#post-password');

        if ('password' === val) {
            password.removeClass('hidden');
        } else {
            password.addClass('hidden');
        }
    });
    
    // Draft deletion confirmation
    $('.edit-draft-notice a').click(function () {
        if (confirm('<?php _e('Delete this draft?'); ?>')) {
            window.location.href = $(this).attr('href');
        }

        return false;
    });
});
</script>

