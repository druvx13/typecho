<?php if (!defined('__TYPECHO_ADMIN__')) exit; ?>
<?php
$fields = isset($post) ? $post->getFieldItems() : $page->getFieldItems();
$defaultFields = isset($post) ? $post->getDefaultFieldItems() : $page->getDefaultFieldItems();
?>
<details id="custom-field"
         class="typecho-post-option" <?php if (!empty($defaultFields) || !empty($fields)): ?>open<?php endif; ?>>
    <summary><?php _e('Customize fields'); ?></summary>
    <ul class="fields mono">
        <?php foreach ($defaultFields as $field): ?>
            <?php [$label, $input] = $field; ?>
            <li class="field">
                <div class="field-name"><?php $label->render(); ?></div>
                <div class="field-value"><?php $input->render(); ?></div>
            </li>
        <?php endforeach; ?>
        <?php foreach ($fields as $field): ?>
            <li class="field">
                <div class="field-name">
                    <label for="fieldname" class="sr-only"><?php _e('Field name'); ?></label>
                    <input type="text" name="fieldNames[]" value="<?php echo htmlspecialchars($field['name']); ?>"
                           id="fieldname" pattern="^[_a-zA-Z][_a-zA-Z0-9]*$" oninput="this.reportValidity()" class="text-s w-100">
                    <label for="fieldtype" class="sr-only"><?php _e('Field type'); ?></label>
                    <select name="fieldTypes[]" id="fieldtype">
                        <option
                            value="str"<?php if ('str' == $field['type']): ?> selected<?php endif; ?>><?php _e('Character'); ?></option>
                        <option
                            value="int"<?php if ('int' == $field['type']): ?> selected<?php endif; ?>><?php _e('Integer'); ?></option>
                        <option
                            value="float"<?php if ('float' == $field['type']): ?> selected<?php endif; ?>><?php _e('Float'); ?></option>
                        <option
                            value="json"<?php if ('json' == $field['type']): ?> selected<?php endif; ?>><?php _e('JSON structure'); ?></option>
                    </select>
                </div>
                <div class="field-value">
                    <label for="fieldvalue" class="sr-only"><?php _e('Field value'); ?></label>
                    <textarea name="fieldValues[]" id="fieldvalue" class="text-s w-100"
                              rows="2"><?php echo htmlspecialchars($field[($field['type'] == 'json' ? 'str' : $field['type']) . '_value']); ?></textarea>
                    <button type="button" class="btn btn-xs"><?php _e('Delete'); ?></button>
                </div>
            </li>
        <?php endforeach; ?>
        <?php if (empty($defaultFields) && empty($fields)): ?>
            <li class="field">
                <div class="field-name">
                    <label for="fieldname" class="sr-only"><?php _e('Field name'); ?></label>
                    <input type="text" name="fieldNames[]" placeholder="<?php _e('Field name'); ?>" id="fieldname"
                           class="text-s w-100" pattern="^[_a-zA-Z][_a-zA-Z0-9]*$" oninput="this.reportValidity()">
                    <label for="fieldtype" class="sr-only"><?php _e('Field type'); ?></label>
                    <select name="fieldTypes[]" id="fieldtype">
                        <option value="str"><?php _e('Character'); ?></option>
                        <option value="int"><?php _e('Integer'); ?></option>
                        <option value="float"><?php _e('Float'); ?></option>
                        <option value="json"><?php _e('JSON structure'); ?></option>
                    </select>
                </div>
                <div class="field-value">
                    <label for="fieldvalue" class="sr-only"><?php _e('Field value'); ?></label>
                    <textarea name="fieldValues[]" placeholder="<?php _e('Field value'); ?>" id="fieldvalue"
                              class="text-s w-100" rows="2"></textarea>
                    <button type="button" class="btn btn-xs"><?php _e('Delete'); ?></button>
                </div>
            </li>
        <?php endif; ?>
    </ul>
    <div class="add">
        <button type="button" class="btn btn-xs operate-add"><?php _e('+Add a field'); ?></button>
        <div class="description kit-hidden-mb">
            <?php _e('Custom fields extend your template functionality. See the <a href="https://docs.typecho.org/help/custom-fields">documentation</a> for usage.'); ?>
        </div>
    </div>
</details>
