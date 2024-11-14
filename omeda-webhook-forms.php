<?php
/*
* Plugin Name: Omeda Webhook Forms
* Description: Captures form data and sends them to a webhook endpoint within the Omeda platform
* Version: 1.0.5
* Author: Adam Greenwell
* Text Domain: omeda-webhook-forms
* File: omeda-webhook-forms.php
*/

if (!defined('ABSPATH')) exit;

require_once plugin_dir_path(__FILE__) . 'includes/class-omeda-webhook-forms-admin.php';
require_once plugin_dir_path(__FILE__) . 'update/github-updater.php';

$updater = new GitHub_Updater(__FILE__);
$updater->set_github_info('adamgreenwell', 'omeda-webhook-forms');

class OmedaWebhookForms
{
	private $plugin_name;
	private $version;
	private $option_name;
	private $admin;

	public function __construct()
	{
		$this->plugin_name = 'omeda-form-handler';
		$this->version = '1.0.5';
		$this->option_name = 'omeda_forms';

		// Initialize admin if in admin area
		if (is_admin()) {
			$this->admin = new OmedaAdmin($this->plugin_name, $this->version, $this->option_name);
		}

		add_shortcode('omeda_form', array($this, 'render_form_shortcode'));
		add_action('wp_ajax_submit_omeda_form', array($this, 'handle_form_submission'));
		add_action('wp_ajax_nopriv_submit_omeda_form', array($this, 'handle_form_submission'));
		add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
	}

	public function enqueue_scripts()
	{
		wp_enqueue_script('omeda-form-handler', plugins_url('assets/js/form-handler.js', __FILE__), array('jquery'), $this->version, true);

		wp_localize_script('omeda-form-handler', 'omedaAjax', array('ajaxurl' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('omeda_form_nonce')));
	}

	public function handle_form_submission()
	{
		check_ajax_referer('omeda_form_nonce', 'nonce');

		$form_id = $_POST['form_id'];
		$forms = get_option($this->option_name);

		if (!isset($forms[$form_id])) {
			$this->log_error('Form not found', array('form_id' => $form_id));
			wp_send_json_error('Form not found');
		}

		$form = $forms[$form_id];
		$webhook_url = $form['webhook_url'];

		// Prepare form data as a proper JSON object
		$json_data = new stdClass();

		foreach ($form['fields'] as $field => $config) {
			$field_name = is_array($config) ? $field : $config;
			if (isset($_POST[$field_name])) {
				$camel_case_field = $this->to_camel_case($field_name);
				$json_data->$camel_case_field = sanitize_text_field($_POST[$field_name]);
			}
		}

		$json_data->formId = $form_id;
		$json_data->submittedAt = gmdate('Y-m-d\TH:i:s\Z');

		$this->log_error('Preparing webhook request', array('url' => $webhook_url, 'json_data' => $json_data));
	
		$response = wp_remote_post($webhook_url, array(
			'body' => json_encode($json_data),
			'headers' => array(
				'Content-Type' => 'application/json',
				'Accept' => 'application/json'
			),
			'timeout' => 30,
			'sslverify' => true,
			'data_format' => 'body'
		));
	
		if (is_wp_error($response)) {
			$this->log_error('WordPress error during submission', array(
				'error_message' => $response->get_error_message(),
				'error_code' => $response->get_error_code()
			));
			wp_send_json_error('Failed to submit form: ' . $response->get_error_message());
		}
	
		$response_code = wp_remote_retrieve_response_code($response);
		$response_body = wp_remote_retrieve_body($response);
	
		$this->log_error('Webhook response received', array(
			'response_code' => $response_code,
			'response_body' => $response_body
		));
	
		if ($response_code !== 200) {
			$error_message = 'Failed to submit form. ';
			if (!empty($response_body)) {
				$decoded_response = json_decode($response_body, true);
				if ($decoded_response && isset($decoded_response['message'])) {
					$error_message .= $decoded_response['message'];
				} else {
					$error_message .= 'HTTP ' . $response_code . ': ' . wp_remote_retrieve_response_message($response);
				}
			}
			wp_send_json_error($error_message);
		}
	
		// Return success with custom message
		wp_send_json_success(array(
			'message' => wp_kses_post($form['success_message'] ?? 'Form submitted successfully')
		));
	}

	private function log_error($message, $data = array())
	{
		if (get_option('omeda_debug_mode', false)) {
			error_log('Omeda Form Debug: ' . $message);
			if (!empty($data)) {
				error_log('Debug Data: ' . print_r($data, true));
			}
		}
	}

	private function to_camel_case($string)
	{
		$string = str_replace(['-', '_'], ' ', strtolower($string));
		$string = str_replace(' ', '', ucwords($string));
		return lcfirst($string);
	}

	public function render_form_shortcode($atts)
	{
		// Parse shortcode attributes
		$atts = shortcode_atts(array('id' => ''), $atts, 'omeda_form');

		if (empty($atts['id'])) {
			return '<p class="omeda-form-error">Error: Form ID is required.</p>';
		}

		$forms = get_option($this->option_name);
		if (!isset($forms[$atts['id']])) {
			return '<p class="omeda-form-error">Error: Form not found.</p>';
		}

		$form = $forms[$atts['id']];

		// Start output buffering
		ob_start();
		?>
		<div class="omeda-form-wrapper">
			<form class="omeda-form" data-form-id="<?php echo esc_attr($atts['id']); ?>">
				<div class="form-messages"></div>

				<?php foreach ($form['fields'] as $field => $config):
					$field_name = is_array($config) ? $field : $config;
					$field_label = is_array($config) ? $config['label'] : ucwords(str_replace(['_', '-'], ' ', $field_name));
					?>
					<div class="form-field">
						<label for="<?php echo esc_attr($field_name); ?>">
							<?php echo esc_html($field_label); ?>
						</label>
						<input
								type="text"
								name="<?php echo esc_attr($field_name); ?>"
								id="<?php echo esc_attr($field_name); ?>"
								class="omeda-input"
								required
						>
					</div>
				<?php endforeach; ?>

				<div class="form-submit">
					<button type="submit" class="omeda-submit-button">Submit</button>
				</div>
			</form>
		</div>

		<style>
					.omeda-form-wrapper {
						max-width: 600px;
						margin: 20px 0;
					}

					.omeda-form .form-field {
						margin-bottom: 15px;
					}

					.omeda-form label {
						display: block;
						margin-bottom: 5px;
						font-weight: bold;
					}

					.omeda-form .omeda-input {
						width: 100%;
						padding: 8px;
						border: 1px solid #ddd;
						border-radius: 4px;
					}

					.omeda-form .form-submit {
						margin-top: 20px;
					}

					.omeda-submit-button {
						background-color: #0073aa;
						color: white;
						padding: 10px 20px;
						border: none;
						border-radius: 4px;
						cursor: pointer;
					}

					.omeda-submit-button:hover {
						background-color: #005177;
					}

					.form-messages {
						margin-bottom: 20px;
					}

					.form-messages .success-message {
						color: green;
						padding: 10px;
						background-color: #e7f6e7;
						border: 1px solid #c3e6c3;
						border-radius: 4px;
					}

					.form-messages .error-message {
						color: #721c24;
						padding: 10px;
						background-color: #f8d7da;
						border: 1px solid #f5c6cb;
						border-radius: 4px;
					}
		</style>
		<?php
		// Return the buffered content
		return ob_get_clean();
	}
}

// Initialize the plugin
$omeda_form_handler = new OmedawebhookForms();