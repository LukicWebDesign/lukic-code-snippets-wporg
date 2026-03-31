<?php
/**
 * Security Headers Manager Snippet
 *
 * Enhances site security by managing HTTP security headers with presets and testing tools.
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Lukic_Security_Headers {
	/**
	 * Option name for storing settings
	 */
	private $option_name = 'Lukic_security_headers';

	/**
	 * Current settings
	 */
	private $settings;

	/**
	 * Default headers configuration
	 */
	private $default_headers = array(
		'x-content-type-options'    => array(
			'enabled'     => true,
			'value'       => 'nosniff',
			'description' => 'Prevents MIME type sniffing',
		),
		'x-frame-options'           => array(
			'enabled'     => true,
			'value'       => 'SAMEORIGIN',
			'description' => 'Controls if site can be embedded in iframes',
		),
		'x-xss-protection'          => array(
			'enabled'     => true,
			'value'       => '1; mode=block',
			'description' => 'Enables browser\'s XSS filtering',
		),
		'referrer-policy'           => array(
			'enabled'     => true,
			'value'       => 'strict-origin-when-cross-origin',
			'description' => 'Controls referrer information in requests',
		),
		'permissions-policy'        => array(
			'enabled'     => false,
			'value'       => 'camera=(), microphone=(), geolocation=()',
			'description' => 'Controls browser feature permissions',
		),
		'strict-transport-security' => array(
			'enabled'     => false,
			'value'       => 'max-age=31536000; includeSubDomains',
			'description' => 'Forces HTTPS connections',
		),
		'content-security-policy'   => array(
			'enabled'     => false,
			'value'       => "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline';",
			'description' => 'Controls which resources can load',
		),
	);

	/**
	 * Constructor
	 */
	public function __construct() {
		// Initialize the snippet
		add_action( 'init', array( $this, 'init' ) );

		// Add submenu page with a priority of 20 to ensure it appears after the main menu
		add_action( 'admin_menu', array( $this, 'add_submenu_page' ), 20 );

		// Enqueue scripts
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		// Register settings
		add_action( 'admin_init', array( $this, 'register_settings' ) );

		// Add security headers
		add_action( 'send_headers', array( $this, 'add_security_headers' ) );

		// Add AJAX handlers for testing
		add_action( 'wp_ajax_lukic_test_security_headers', array( $this, 'ajax_test_headers' ) );
	}

	/**
	 * Initialize the snippet
	 */
	public function init() {
		// Get saved settings or use defaults
		$this->settings = get_option( $this->option_name, $this->default_headers );
	}

	/**
	 * Add submenu page
	 */
	public function add_submenu_page() {
		add_submenu_page(
			'lukic-code-snippets',
			__( 'Security Headers', 'lukic-code-snippets' ),
			__( 'Security Headers', 'lukic-code-snippets' ),
			'manage_options',
			'lukic-security-headers',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Enqueue scripts
	 */
	public function enqueue_scripts( $hook ) {
		if ( strpos( $hook, 'lukic-security-headers' ) === false ) {
			return;
		}

		wp_enqueue_script( 'jquery' );
		wp_localize_script( 'jquery', 'LukicSecHeaders', array(
			'nonce'    => wp_create_nonce( 'security_headers_test' ),
			'testing'  => __( 'Testing headers...', 'lukic-code-snippets' ),
			'error'    => __( 'Error testing headers', 'lukic-code-snippets' ),
			'present'  => __( 'Present', 'lukic-code-snippets' ),
			'missing'  => __( 'Missing', 'lukic-code-snippets' ),
			'current'  => __( 'Current', 'lukic-code-snippets' ),
			'expected' => __( 'Expected', 'lukic-code-snippets' ),
		) );

		wp_add_inline_script( 'jquery', '
			jQuery(document).ready(function($) {
				$("#test-headers").on("click", function() {
					var $button = $(this);
					var $results = $("#test-results");
					var $content = $(".test-results-content");

					$button.prop("disabled", true);
					$button.addClass("is-loading");
					$content.html("<p class=\"Lukic-loading\">" + LukicSecHeaders.testing + "</p>");
					$results.show();

					$.ajax({
						url: ajaxurl,
						type: "POST",
						data: {
							action: "lukic_test_security_headers",
							nonce: LukicSecHeaders.nonce
						},
						success: function(response) {
							if (response.success) {
								var html = "";
								$.each(response.data, function(header, result) {
									var status = result.present && result.value === result.expected ? "success" : "error";
									html += "<div class=\"header-test-result " + status + "\">";
									html += "<div class=\"header-test-result__title\">" + header.toUpperCase() + "</div>";
									html += "<div class=\"header-test-result__status\">" + (result.present ? "✔ " + LukicSecHeaders.present : "✖ " + LukicSecHeaders.missing) + "</div>";
									if (result.present) {
										html += "<div class=\"header-test-result__value\"><strong>" + LukicSecHeaders.current + ":</strong> " + result.value + "</div>";
										html += "<div class=\"header-test-result__value\"><strong>" + LukicSecHeaders.expected + ":</strong> " + result.expected + "</div>";
									}
									html += "</div>";
								});
								$content.html(html);
							} else {
								var message = response.data ? response.data : LukicSecHeaders.error;
								$content.html("<p class=\"error\">" + message + "</p>");
							}
						},
						error: function() {
							$content.html("<p class=\"error\">" + LukicSecHeaders.error + "</p>");
						},
						complete: function() {
							$button.prop("disabled", false);
							$button.removeClass("is-loading");
						}
					});
				});
			});
		' );
	}

	/**
	 * Register settings
	 */
	public function register_settings() {
		register_setting(
			'Lukic_security_headers_group',
			$this->option_name,
			array( $this, 'sanitize_settings' )
		);
	}

	/**
	 * Sanitize settings
	 */
	public function sanitize_settings( $input ) {
		$sanitized = array();

		foreach ( $this->default_headers as $header => $default_config ) {
			if ( isset( $input[ $header ] ) ) {
				$sanitized[ $header ] = array(
					'enabled'     => isset( $input[ $header ]['enabled'] ) ? (bool) $input[ $header ]['enabled'] : false,
					'value'       => sanitize_text_field( $input[ $header ]['value'] ),
					'description' => $default_config['description'],
				);
			} else {
				$sanitized[ $header ] = $default_config;
			}
		}

		return $sanitized;
	}

	/**
	 * Add security headers
	 */
	public function add_security_headers() {
		if ( ! is_admin() ) {
			foreach ( $this->settings as $header => $config ) {
				if ( $config['enabled'] ) {
					// Convert header name to HTTP header format
					$header_name = implode( '-', array_map( 'ucfirst', explode( '-', $header ) ) );
					header( "$header_name: {$config['value']}" );
				}
			}
		}
	}

	/**
	 * AJAX handler for testing headers
	 */
	public function ajax_test_headers() {
		check_ajax_referer( 'security_headers_test', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized' );
		}

		$url      = get_site_url();
		$response = wp_remote_get( $url, array(
			'sslverify' => false,
			'timeout'   => 15,
		) );

		if ( is_wp_error( $response ) ) {
			wp_send_json_error( $response->get_error_message() );
		}

		$headers = wp_remote_retrieve_headers( $response );
		$results = array();

		foreach ( $this->settings as $header => $config ) {
			if ( $config['enabled'] ) {
				$header_name        = implode( '-', array_map( 'ucfirst', explode( '-', $header ) ) );
				$results[ $header ] = array(
					'present'  => isset( $headers[ $header_name ] ),
					'value'    => isset( $headers[ $header_name ] ) ? $headers[ $header_name ] : '',
					'expected' => $config['value'],
				);
			}
		}

		wp_send_json_success( $results );
	}

	/**
	 * Render settings page
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Get current settings
		$settings = $this->settings;

		$total_headers  = count( $settings );
		$active_headers = count(
			array_filter(
				$settings,
				function ( $config ) {

					return ! empty( $config['enabled'] );
				}
			)
		);
		$stats          = array(
			array(
				'count' => $active_headers,
				'label' => __( 'Active Headers', 'lukic-code-snippets' ),
			),
			array(
				'count' => $total_headers,
				'label' => __( 'Total Headers', 'lukic-code-snippets' ),
			),
		);
		?>
		<div class="wrap Lukic-wrap Lukic-security-headers">
			<?php Lukic_display_header( __( 'Security Headers Manager', 'lukic-code-snippets' ), $stats ); ?>

			<div class="Lukic-settings-intro">
				<p><?php esc_html_e( 'Security headers help protect your site against common web vulnerabilities. Enable the headers you need and customize their values to match your security policy.', 'lukic-code-snippets' ); ?></p>
			</div>

			<div class="Lukic-settings-container">
				<form method="post" action="options.php" class="Lukic-security-form">
					<?php settings_fields( 'Lukic_security_headers_group' ); ?>

					<div class="Lukic-security-grid">
						<?php
						foreach ( $settings as $header => $config ) :
							$is_enabled = ! empty( $config['enabled'] );
							?>
							<div class="Lukic-card Lukic-security-card <?php echo $is_enabled ? 'is-active' : ''; ?>">
								<div class="Lukic-card-header">
									<div>
										<h3><?php echo esc_html( strtoupper( str_replace( '-', ' ', $header ) ) ); ?></h3>
										<p class="Lukic-description"><?php echo esc_html( $config['description'] ); ?></p>
									</div>
									<label class="Lukic-switch">
										<input type="checkbox" 
											name="<?php echo esc_attr( $this->option_name ); ?>[<?php echo esc_attr( $header ); ?>][enabled]"
											<?php checked( $is_enabled ); ?>>
										<span class="Lukic-slider"></span>
									</label>
								</div>

								<div class="Lukic-field-control">
									<label><?php esc_html_e( 'Header Value', 'lukic-code-snippets' ); ?></label>
									<input type="text"
										class="Lukic-input"
										name="<?php echo esc_attr( $this->option_name ); ?>[<?php echo esc_attr( $header ); ?>][value]"
										value="<?php echo esc_attr( $config['value'] ); ?>">
								</div>
							</div>
						<?php endforeach; ?>
					</div>

					<div class="Lukic-actions">
						<?php submit_button( __( 'Save Changes', 'lukic-code-snippets' ), 'Lukic-btn Lukic-btn--primary', 'submit', false ); ?>
						<button type="button" class="Lukic-btn Lukic-btn--secondary" id="test-headers">
							<?php esc_html_e( 'Test Headers', 'lukic-code-snippets' ); ?>
						</button>
					</div>
				</form>
			</div>

			<div id="test-results" class="Lukic-card Lukic-test-results" style="display: none;">
				<h2><?php esc_html_e( 'Test Results', 'lukic-code-snippets' ); ?></h2>
				<div class="test-results-content"></div>
			</div>
		</div>

		<?php
	}
}

// Initialize the snippet
$Lukic_security_headers = new Lukic_Security_Headers();
