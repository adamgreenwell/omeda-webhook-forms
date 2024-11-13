<?php

/*
* File: admin/partials/forms-list.php
*/

?>
<div class="wrap">
    <h1 class="wp-heading-inline">Omeda Forms</h1>
    <a href="<?php echo admin_url('admin.php?page=omeda-forms-new'); ?>" class="page-title-action">Add New</a>

    <?php if (isset($_GET['message']) && $_GET['message'] === 'success'): ?>
        <div class="notice notice-success is-dismissible">
            <p>Form saved successfully.</p>
        </div>
    <?php endif; ?>

    <?php
    $forms = get_option($this->option_name, array());
    if (!empty($forms)): ?>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>Form Name</th>
                <th>Webhook URL</th>
                <th>Shortcode</th>
                <th>Created</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($forms as $form_id => $form): ?>
            <tr>
                <td><?php echo esc_html($form['name']); ?></td>
                <td><?php echo esc_url($form['webhook_url']); ?></td>
                <td><code>[omeda_form id="<?php echo esc_attr($form_id); ?>"]</code></td>
                <td><?php echo esc_html($form['created_at']); ?></td>
                <td>
                    <a href="<?php echo admin_url('admin.php?page=omeda-forms-new&form_id=' . urlencode($form_id)); ?>">Edit</a> |
                    <a href="#" class="delete-form" data-id="<?php echo esc_attr($form_id); ?>"
                       data-name="<?php echo esc_attr($form['name']); ?>">Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
        <p>No forms found. <a href="<?php echo admin_url('admin.php?page=omeda-forms-new'); ?>">Create your first form</a>.</p>
    <?php endif; ?>
</div>

<script>
jQuery(document).ready(function($) {
    $('.delete-form').on('click', function(e) {
        e.preventDefault();

        var formId = $(this).data('id');
        var formName = $(this).data('name');

        if (confirm('Are you sure you want to delete the form "' + formName + '"? This action cannot be undone.')) {
            var $row = $(this).closest('tr');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'delete_omeda_form',
                    form_id: formId,
                    nonce: '<?php echo wp_create_nonce("delete_form_nonce"); ?>'
                },
                beforeSend: function() {
                    $row.css('opacity', '0.5');
                },
                success: function(response) {
                    if (response.success) {
                        $row.fadeOut(400, function() {
                            $row.remove();
                            // If no more forms, reload the page to show the "No forms" message
                            if ($('table tbody tr').length === 1) {
                                window.location.href = '<?php echo admin_url('admin.php?page=omeda-forms&message=deleted'); ?>';
                            }
                        });
                    } else {
                        alert('Error deleting form: ' + (response.data || 'Unknown error'));
                        $row.css('opacity', '1');
                    }
                },
                error: function(xhr, status, error) {
                    alert('Error deleting form. Please try again.');
                    $row.css('opacity', '1');
                    console.error('Ajax error:', status, error);
                }
            });
        }
    });

    // Make notice messages dismissible
    $(document).on('click', '.notice-dismiss', function() {
        $(this).closest('.notice').fadeOut();
    });
});
</script>

<style>
.wp-list-table .delete-form {
    color: #a00;
}
.wp-list-table .delete-form:hover {
    color: #dc3232;
    text-decoration: none;
}
</style>