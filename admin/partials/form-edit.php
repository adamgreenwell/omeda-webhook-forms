<?php

/**
 * File: admin/partials/form-edit.php
 */

if (!defined('ABSPATH')) exit;

// Get form ID from URL if editing
$form_id = isset($_GET['form_id']) ? sanitize_text_field($_GET['form_id']) : '';

// Get existing forms
$forms = get_option($this->option_name, array());

// Get form data if editing, otherwise empty defaults
$form = isset($forms[$form_id]) ? $forms[$form_id] : array(
    'name' => '',
    'webhook_url' => '',
    'fields' => array(''),  // Start with one empty field
);

// Get any error messages
$error_type = isset($_GET['error']) ? sanitize_text_field($_GET['error']) : '';
?>

<div class="wrap">
    <h1><?php echo $form_id ? 'Edit Form' : 'Add New Form'; ?></h1>

    <?php if ($error_type === 'missing_fields'): ?>
    <div class="notice notice-error is-dismissible">
        <p>Please fill in all required fields.</p>
    </div>
    <?php elseif ($error_type === 'save_failed'): ?>
    <div class="notice notice-error is-dismissible">
        <p>Failed to save form. Please try again.</p>
    </div>
    <?php endif; ?>

    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
        <input type="hidden" name="action" value="save_omeda_form">
        <?php wp_nonce_field('save_omeda_form', 'omeda_form_nonce'); ?>
        <input type="hidden" name="form_id" value="<?php echo esc_attr($form_id); ?>">

        <table class="form-table">
            <tr>
                <th scope="row"><label for="form_name">Form Name</label></th>
                <td>
                    <input type="text" id="form_name" name="name" class="regular-text"
                           value="<?php echo esc_attr($form['name']); ?>" required>
                    <p class="description">Enter a name to identify this form.</p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="webhook_url">Webhook URL</label></th>
                <td>
                    <input type="url" id="webhook_url" name="webhook_url" class="regular-text"
                           value="<?php echo esc_url($form['webhook_url']); ?>" required>
                    <p class="description">Enter the Omeda webhook URL where form data will be sent.</p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="form_fields">Form Fields</label></th>
                <td>
                    <div id="field-container">
                        <?php
                        if (!empty($form['fields'])):
                            foreach ($form['fields'] as $field): ?>
                                <div class="field-row">
                                    <input type="text" name="fields[]" value="<?php echo esc_attr($field); ?>"
                                           class="regular-text" placeholder="Field name (e.g., email, first_name)" required>
                                    <button type="button" class="button remove-field">Remove</button>
                                </div>
                            <?php endforeach;
                        else: ?>
                            <div class="field-row">
                                <input type="text" name="fields[]" class="regular-text"
                                       placeholder="Field name (e.g., email, first_name)" required>
                                <button type="button" class="button remove-field">Remove</button>
                            </div>
                        <?php endif; ?>
                    </div>
                    <button type="button" class="button add-field">Add Field</button>
                    <p class="description">Add the fields you want to collect in your form.</p>
                </td>
            </tr>
        </table>

        <?php submit_button($form_id ? 'Update Form' : 'Create Form'); ?>
    </form>
</div>

<style>
.field-row {
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 10px;
}
.field-row input {
    flex: 1;
}
.field-row .button {
    flex-shrink: 0;
}
</style>

<script>
jQuery(document).ready(function($) {
    $('.add-field').on('click', function() {
        var fieldHtml = '<div class="field-row">' +
            '<input type="text" name="fields[]" class="regular-text" ' +
            'placeholder="Field name (e.g., email, first_name)" required>' +
            '<button type="button" class="button remove-field">Remove</button>' +
            '</div>';
        $('#field-container').append(fieldHtml);
    });

    $('#field-container').on('click', '.remove-field', function() {
        if ($('.field-row').length > 1) {
            $(this).closest('.field-row').remove();
        } else {
            alert('You must have at least one field.');
        }
    });
});
</script>