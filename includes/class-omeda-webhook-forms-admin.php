<?php
class OmedaAdmin {
    private $plugin_name;
    private $version;
    private $option_name;

    public function __construct($plugin_name, $version, $option_name) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->option_name = $option_name;

        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_post_save_omeda_form', array($this, 'handle_form_save'));
        add_action('wp_ajax_delete_omeda_form', array($this, 'handle_form_deletion'));
    }

    public function add_admin_menu() {
        add_menu_page(
            'Omeda Forms',
            'Omeda Forms',
            'manage_options',
            'omeda-forms',
            array($this, 'display_forms_list_page'),
            'dashicons-feedback',
            30
        );

        add_submenu_page(
            'omeda-forms',
            'All Forms',
            'All Forms',
            'manage_options',
            'omeda-forms',
            array($this, 'display_forms_list_page')
        );

        add_submenu_page(
            'omeda-forms',
            'Add New Form',
            'Add New Form',
            'manage_options',
            'omeda-forms-new',
            array($this, 'display_form_edit_page')
        );
    }

    public function display_forms_list_page() {
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/forms-list.php';
    }

    public function display_form_edit_page() {
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/form-edit.php';
    }

    public function handle_form_deletion() {
    // Verify nonce and capabilities
    check_ajax_referer('delete_form_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error('You do not have permission to delete forms.');
        return;
    }

    // Get and sanitize form ID
    $form_id = isset($_POST['form_id']) ? sanitize_text_field($_POST['form_id']) : '';

    if (empty($form_id)) {
        wp_send_json_error('No form ID provided.');
        return;
    }

    // Get current forms
    $forms = get_option($this->option_name, array());

    // Check if form exists
    if (!isset($forms[$form_id])) {
        wp_send_json_error('Form not found.');
        return;
    }

    // Remove the form
    unset($forms[$form_id]);

    // Update the option
    $updated = update_option($this->option_name, $forms);

    if ($updated) {
        wp_send_json_success(array(
            'message' => 'Form deleted successfully.',
            'form_id' => $form_id
        ));
    } else {
        wp_send_json_error('Failed to delete form. Please try again.');
    }
}

	public function handle_form_save() {
		if (!current_user_can('manage_options')) {
			wp_die('Unauthorized');
		}

		check_admin_referer('save_omeda_form', 'omeda_form_nonce');

		// Get and sanitize form data
		$form_data = array('name' => isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '', 'webhook_url' => isset($_POST['webhook_url']) ? esc_url_raw($_POST['webhook_url']) : '', 'fields' => isset($_POST['fields']) ? array_filter(array_map('sanitize_text_field', $_POST['fields'])) : array(), 'modified_at' => current_time('mysql'));

		// Validate required fields
		if (empty($form_data['name']) || empty($form_data['webhook_url']) || empty($form_data['fields'])) {
			wp_redirect(add_query_arg(array('page' => 'omeda-forms-new', 'message' => 'error', 'error' => 'missing_fields'), admin_url('admin.php')));
			exit;
		}

		// Get existing forms
		$forms = get_option($this->option_name, array());

		// Get form ID from POST or generate new one
		$form_id = !empty($_POST['form_id']) ? sanitize_text_field($_POST['form_id']) : 'form_' . uniqid();

		// For new forms, add created_at timestamp
		if (!isset($forms[$form_id])) {
			$form_data['created_at'] = current_time('mysql');
		} else {
			// Preserve created_at for existing forms
			$form_data['created_at'] = $forms[$form_id]['created_at'];
		}

		// Save the form
		$forms[$form_id] = $form_data;

		if (update_option($this->option_name, $forms)) {
			wp_redirect(add_query_arg(array('page' => 'omeda-forms', 'message' => 'success'), admin_url('admin.php')));
		} else {
			wp_redirect(add_query_arg(array('page' => 'omeda-forms-new', 'message' => 'error', 'error' => 'save_failed'), admin_url('admin.php')));
		}
		exit;
	}
}