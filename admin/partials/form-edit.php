<?php

/*
* File: admin/partials/form-edit.php
*/

if (!defined('ABSPATH')) exit;

// Get form ID from URL if editing
$form_id = isset($_GET['form_id']) ? sanitize_text_field($_GET['form_id']) : '';

// Get existing forms
$forms = get_option($this->option_name, array());

// Get form data if editing, otherwise empty defaults
$form = isset($forms[$form_id]) ? $forms[$form_id] : array('name' => '', 'webhook_url' => '', 'fields' => array(''),  // Start with one empty field
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
					<div id="field-container" class="sortable">
						<?php
						if (!empty($form['fields'])):
							foreach ($form['fields'] as $field => $config):
								$field_name = is_array($config) ? $field : $config;
								$field_label = is_array($config) ? $config['label'] : '';
								?>
								<div class="field-row">
									<div class="drag-handle dashicons dashicons-menu"></div>
									<div class="field-inputs">
										<input type="text" name="fields[]" value="<?php echo esc_attr($field_name); ?>"
											   class="regular-text field-name"
											   placeholder="Field name (e.g., email, first_name)" required>
										<input type="text" name="labels[]" value="<?php echo esc_attr($field_label); ?>"
											   class="regular-text field-label"
											   placeholder="Display Label (e.g., Email Address)">
									</div>
									<button type="button" class="button remove-field">Remove</button>
								</div>
							<?php endforeach;
						else: ?>
							<div class="field-row">
								<div class="drag-handle dashicons dashicons-menu"></div>
								<div class="field-inputs">
									<input type="text" name="fields[]" class="regular-text field-name"
										   placeholder="Field name (e.g., email, first_name)" required>
									<input type="text" name="labels[]" class="regular-text field-label"
										   placeholder="Display Label (e.g., Email Address)">
								</div>
								<button type="button" class="button remove-field">Remove</button>
							</div>
						<?php endif; ?>
					</div>
					<button type="button" class="button add-field">Add Field</button>
					<p class="description">Add the fields you want to collect in your form. Drag and drop to reorder
						fields. Field name should be a technical name (lowercase, no spaces). Label is what users will
						see on the form.</p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="success_message">Success Message</label></th>
				<td>
					<textarea id="success_message" name="success_message" class="large-text" rows="3"><?php
						echo esc_textarea(isset($form['success_message']) ? $form['success_message'] : 'Thank you! Your form has been submitted successfully.');
						?></textarea>
					<p class="description">Enter the message to display when the form is submitted successfully.</p>
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
		align-items: flex-start;
		gap: 10px;
		background: #fff;
		padding: 10px;
		border: 1px solid #ddd;
		border-radius: 4px;
		cursor: move;
	}

	.field-row:hover {
		border-color: #999;
	}

	.field-inputs {
		flex: 1;
		display: flex;
		flex-direction: column;
		gap: 5px;
	}

	.field-inputs input {
		width: 100%;
	}

	.field-row .button {
		flex-shrink: 0;
		margin-top: 8px;
	}

	.field-name {
		font-family: monospace;
	}

	.drag-handle {
		color: #999;
		cursor: move;
		padding: 10px 0;
	}

	.field-row:hover .drag-handle {
		color: #666;
	}

	.sortable-placeholder {
		border: 2px dashed #ccc;
		margin-bottom: 10px;
		height: 90px;
		border-radius: 4px;
	}

	.ui-sortable-helper {
		background: #fff;
		box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
	}
</style>

<script>
	jQuery(document).ready(function ($) {
// Initialize sortable
		$('#field-container').sortable({
			handle: '.drag-handle',
			placeholder: 'sortable-placeholder',
			forcePlaceholderSize: true,
			opacity: 0.8,
			tolerance: 'pointer'
		});

		$('.add-field').on('click', function () {
			var fieldHtml = '<div class="field-row">' +
				'<div class="drag-handle dashicons dashicons-menu"></div>' +
				'<div class="field-inputs">' +
				'<input type="text" name="fields[]" class="regular-text field-name" ' +
				'placeholder="Field name (e.g., email, first_name)" required>' +
				'<input type="text" name="labels[]" class="regular-text field-label" ' +
				'placeholder="Display Label (e.g., Email Address)">' +
				'</div>' +
				'<button type="button" class="button remove-field">Remove</button>' +
				'</div>';
			$('#field-container').append(fieldHtml);
		});

		$('#field-container').on('click', '.remove-field', function () {
			if ($('.field-row').length > 1) {
				$(this).closest('.field-row').remove();
			} else {
				alert('You must have at least one field.');
			}
		});

		// Auto-generate label from field name
		$('#field-container').on('blur', '.field-name', function () {
			var $labelInput = $(this).closest('.field-inputs').find('.field-label');
			if ($labelInput.val() === '') {
				var fieldName = $(this).val();
				var label = fieldName
					.replace(/[_-]/g, ' ')
					.replace(/([A-Z])/g, ' $1')
					.replace(/\w\S*/g, function (txt) {
						return txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase();
					});
				$labelInput.val(label);
			}
		});
	});
</script>