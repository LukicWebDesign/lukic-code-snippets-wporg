<?php
/**
 * Custom Admin Footer Text
 *
 * This snippet allows you to customize the text at the bottom of the WordPress
 * admin interface, or remove it entirely.
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class Lukic_Custom_Admin_Footer
 */
class Lukic_Custom_Admin_Footer {

	/**
	 * Custom admin footer text option name
	 */
	private $option_name = 'Lukic_custom_admin_footer_text';

	/**
	 * Constructor
	 */
	public function __construct() {
		// Add submenu page
		add_action( 'admin_menu', array( $this, 'add_submenu_page' ) );

		// Register settings
		add_action( 'admin_init', array( $this, 'register_settings' ) );

		// Filter admin left footer text
		add_filter( 'admin_footer_text', array( $this, 'custom_admin_footer_left_text' ), 99 );
		
		// Filter admin right footer text
		add_filter( 'update_footer', array( $this, 'custom_admin_footer_right_text' ), 99 );
	}

	/**
	 * Add submenu page
	 */
	public function add_submenu_page() {
		add_submenu_page(
			'lukic-code-snippets',
			__( 'Custom Admin Footer Text', 'lukic-code-snippets' ),
			__( 'Admin Footer Text', 'lukic-code-snippets' ),
			'manage_options',
			'lukic-custom-admin-footer',
			array( $this, 'display_settings_page' )
		);
	}

	/**
	 * Register settings
	 */
	public function register_settings() {
		register_setting(
			'Lukic_custom_admin_footer_settings',
			$this->option_name,
			array( $this, 'sanitize_settings' )
		);
	}

	/**
	 * Sanitize settings
	 */
	public function sanitize_settings( $input ) {
		// If input is not an array, initialize it
		if ( ! is_array( $input ) ) {
			$input = array();
		}
		
		$sanitized = array(
			'left_text'  => '',
			'right_text' => '',
		);

		// Handle legacy "text" field format if stored that way
		if ( isset( $input['text'] ) && ! isset( $input['left_text'] ) ) {
			$sanitized['left_text'] = wp_kses_post( trim( $input['text'] ) );
		}
		
		if ( isset( $input['left_text'] ) ) {
			$sanitized['left_text'] = wp_kses_post( trim( $input['left_text'] ) );
		}
		
		if ( isset( $input['right_text'] ) ) {
			$sanitized['right_text'] = wp_kses_post( trim( $input['right_text'] ) );
		}

		return $sanitized;
	}

	/**
	 * Display settings page
	 */
	public function display_settings_page() {
		// Default structure
		$defaults = array(
			'left_text'  => '',
			'right_text' => '',
		);
		
		$options = get_option( $this->option_name, $defaults );
		
		// Map legacy format
		if ( isset( $options['text'] ) && ! isset( $options['left_text'] ) ) {
			$options['left_text'] = $options['text'];
		}
		
		$options = wp_parse_args( $options, $defaults );

		$left_text  = $options['left_text'];
		$right_text = $options['right_text'];

		// Stats for header
		$stats = array(
			array(
				'count' => empty( $left_text ) && empty( $right_text ) ? __( 'Hidden', 'lukic-code-snippets' ) : __( 'Custom', 'lukic-code-snippets' ),
				'label' => __( 'Footer Status', 'lukic-code-snippets' ),
			),
		);
		?>
		<div class="wrap Lukic-settings-wrap">
			<?php Lukic_display_header( __( 'Custom Admin Footer Text', 'lukic-code-snippets' ), $stats ); ?>
			
			<div class="Lukic-settings-intro">
				<p><?php esc_html_e( 'Change the text that appears at the bottom left and bottom right of the WordPress admin area, or leave it blank to remove it entirely.', 'lukic-code-snippets' ); ?></p>
			</div>
			
			<div class="Lukic-settings-container">
				<form method="post" action="options.php" class="Lukic-settings-form">
					<?php settings_fields( 'Lukic_custom_admin_footer_settings' ); ?>
					<?php settings_errors(); ?>
					
					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="Lukic_custom_admin_footer_text[left_text]"><?php esc_html_e( 'Left Footer Text', 'lukic-code-snippets' ); ?></label>
							</th>
							<td>
								<input type="text" name="<?php echo esc_attr( $this->option_name ); ?>[left_text]" 
										id="Lukic_custom_admin_footer_text[left_text]" 
										value="<?php echo esc_attr( $left_text ); ?>" class="regular-text">
								<p class="description">
									<?php esc_html_e( 'Enter your custom left footer text (e.g. "Thank you for creating with WordPress"). Use HTML for links. Leave empty to hide.', 'lukic-code-snippets' ); ?>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="Lukic_custom_admin_footer_text[right_text]"><?php esc_html_e( 'Right Footer Text', 'lukic-code-snippets' ); ?></label>
							</th>
							<td>
								<input type="text" name="<?php echo esc_attr( $this->option_name ); ?>[right_text]" 
										id="Lukic_custom_admin_footer_text[right_text]" 
										value="<?php echo esc_attr( $right_text ); ?>" class="regular-text">
								<p class="description">
									<?php esc_html_e( 'Enter your custom right footer text (e.g. WordPress version string). Use HTML for links. Leave empty to hide.', 'lukic-code-snippets' ); ?>
								</p>
							</td>
						</tr>
					</table>
					
					<div class="Lukic-submit-container">
						<?php submit_button( __( 'Save Changes', 'lukic-code-snippets' ), 'primary Lukic-btn Lukic-btn--primary', 'submit', false ); ?>
					</div>
				</form>
			</div>
		</div>
		<?php
	}

	/**
	 * Filter admin left footer text
	 *
	 * @param string $default_text The default left admin footer text.
	 * @return string The custom left admin footer text.
	 */
	public function custom_admin_footer_left_text( $default_text ) {
		// Use empty string as default behavior
		$options = get_option( $this->option_name, array() );
		
		// Backwards compat with first revision
		if ( isset( $options['text'] ) && ! isset( $options['left_text'] ) ) {
			return $options['text'];
		}
		
		if ( isset( $options['left_text'] ) ) {
			return $options['left_text'];
		}
		
		return '';
	}

	/**
	 * Filter admin right footer text
	 *
	 * @param string $default_text The default right admin footer text.
	 * @return string The custom right admin footer text.
	 */
	public function custom_admin_footer_right_text( $default_text ) {
		// Use empty string as default behavior
		$options = get_option( $this->option_name, array() );
		
		if ( isset( $options['right_text'] ) ) {
			return $options['right_text'];
		}
		
		return '';
	}
}

// Initialize the class
new Lukic_Custom_Admin_Footer();
