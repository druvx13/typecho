<?php if(!defined('__TYPECHO_ADMIN__')) exit; ?>
<script>
$(document).ready(function () {
    // Custom fields
    function attachDeleteEvent (el) {
        $('button.btn-xs', el).click(function () {
            if (confirm('<?php _e('Are you sure to delete this field?'); ?>')) {
                $(this).parents('li').fadeOut(function () {
                    $(this).remove();
                });

                $(this).parents('form').trigger('change');
            }
        });
    }

    $('#custom-field .fields .field').each(function () {
        attachDeleteEvent(this);
    });

    $('#custom-field button.operate-add').click(function () {
        var html = '<li class="field"><div class="field-name"><input type="text" name="fieldNames[]" placeholder="<?php _e('Field name'); ?>" pattern="^[_a-zA-Z][_a-zA-Z0-9]*$" oninput="this.reportValidity()" class="text-s w-100">'
                + '<select name="fieldTypes[]" id="">'
                + '<option value="str"><?php _e('Character'); ?></option>'
                + '<option value="int"><?php _e('Integer'); ?></option>'
                + '<option value="float"><?php _e('Float'); ?></option>'
                + '<option value="json"><?php _e('JSON structure'); ?></option>'
                + '</select></div>'
                + '<div class="field-value"><textarea name="fieldValues[]" placeholder="<?php _e('Field value'); ?>" class="text-s w-100" rows="2"></textarea>'
                + '<button type="button" class="btn btn-xs"><?php _e('Delete'); ?></button></div></li>',
            el = $(html).hide().appendTo('#custom-field .fields').fadeIn();

        attachDeleteEvent(el);
    });
});
</script>
