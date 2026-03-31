<?php
/**
 * Custom Login Page URL - redirect WordPress login to a custom URL
 *
 * This snippet allows you to set a custom login page URL and redirects
 * users from the default WordPress login page to your home page.
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class Lukic_Custom_Login_URL
 */
class Lukic_Custom_Login_URL {

	/**
	 * Custom login URL option name
	 */
	private $option_name = 'Lukic_custom_login_url';

	/**
	 * Constructor
	 */
	public function __construct() {
		// Add submenu page
		add_action( 'admin_menu', array( $this, 'add_submenu_page' ) );

		// Register settings
		add_action( 'admin_init', array( $this, 'register_settings' ) );

		// Handle custom login URL
		add_action( 'init', array( $this, 'handle_login_page' ), 1 );

		// Block wp-admin access for non-logged in users
		add_action( 'init', array( $this, 'block_wp_admin' ), 1 );

		// Filter login URL for internal WordPress functions
		add_filter( 'login_url', array( $this, 'custom_login_url' ), 10, 3 );
		add_filter( 'site_url', array( $this, 'filter_site_url' ), 10, 4 );
		add_filter( 'network_site_url', array( $this, 'filter_network_site_url' ), 10, 3 );
		add_filter( 'wp_redirect', array( $this, 'filter_wp_redirect' ), 10, 2 );
		
		// Remove WordPress's default redirect for paths like /login or /admin
		remove_action( 'template_redirect', 'wp_redirect_admin_locations', 1000 );
	}

	/**
	 * Add submenu page
	 */
	public function add_submenu_page() {
		add_submenu_page(
			'lukic-code-snippets',
			__( 'Custom Login URL', 'lukic-code-snippets' ),
			__( 'Custom Login URL', 'lukic-code-snippets' ),
			'manage_options',
			'lukic-custom-login-url',
			array( $this, 'display_settings_page' )
		);
	}

	/**
	 * Register settings
	 */
	public function register_settings() {
		register_setting(
			'Lukic_custom_login_url_settings',
			$this->option_name,
			array( $this, 'sanitize_settings' )
		);
	}

	/**
	 * Sanitize settings
	 */
	public function sanitize_settings( $input ) {
		if ( isset( $input['slug'] ) ) {
			// Remove leading/trailing slashes and sanitize
			$input['slug'] = sanitize_title( trim( $input['slug'], '/' ) );

			// If empty, set a default
			if ( empty( $input['slug'] ) ) {
				$input['slug'] = 'login';
			}

			// Make sure it's not a forbidden slug
			if ( in_array( $input['slug'], $this->forbidden_slugs() ) ) {
				add_settings_error(
					'Lukic_custom_login_url',
					'forbidden_slug',
					/* translators: %s: The slug that was entered by the user */
					sprintf( __( 'The slug "%s" is not allowed. Please choose a different one.', 'lukic-code-snippets' ), $input['slug'] ),
					'error'
				);
				$input['slug'] = 'login';
			}

			// Flush rewrite rules
			flush_rewrite_rules();
		} else {
			$input['slug'] = 'login';
		}

		return $input;
	}

	/**
	 * List of forbidden slugs
	 */
	private function forbidden_slugs() {
		return array(
			'wp-login',
			'wp-admin',
			'admin',
			'administrator',
			'login',
			'wp-login.php',
			'dashboard',
			'profile',
			'register',
			'logout',
			'wp-register.php',
		);
	}

	/**
	 * Display settings page
	 */
	public function display_settings_page() {
		$options  = get_option( $this->option_name, array( 'slug' => 'login' ) );
		$slug     = isset( $options['slug'] ) ? $options['slug'] : 'login';
		$site_url = home_url( '/' );

		// Include the header partial
		// Header component is already loaded in main plugin file

		$login_url = home_url( $slug );
		$stats     = array(
			array(
				'count' => $slug,
				'label' => __( 'Login URL', 'lukic-code-snippets' ),
			),
		);
		?>
		<div class="wrap Lukic-settings-wrap">
			<?php Lukic_display_header( __( 'Custom Login URL', 'lukic-code-snippets' ), $stats ); ?>
			
			<div class="Lukic-settings-intro">
				<p><?php esc_html_e( 'Change the WordPress login URL to improve security by hiding the default wp-login.php page.', 'lukic-code-snippets' ); ?></p>
				<p>
				<?php
				echo wp_kses(
					/* translators: %s: The current login URL */
					sprintf( __( 'Your current login URL: <strong>%s</strong>', 'lukic-code-snippets' ), esc_url( $login_url ) ),
					array( 'strong' => array() )
				);
				?>
				</p>
			</div>
			
			<div class="Lukic-settings-container">
				<form method="post" action="options.php" class="Lukic-settings-form">
					<?php settings_fields( 'Lukic_custom_login_url_settings' ); ?>
					<?php settings_errors(); ?>
					
					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="Lukic_custom_login_url[slug]"><?php esc_html_e( 'Custom Login URL', 'lukic-code-snippets' ); ?></label>
							</th>
							<td>
								<div class="Lukic-url-input-group">
									<span class="Lukic-url-prefix"><?php echo esc_html( $site_url ); ?></span>
									<input type="text" name="<?php echo esc_attr( $this->option_name ); ?>[slug]" 
											id="Lukic_custom_login_url[slug]" 
											value="<?php echo esc_attr( $slug ); ?>" class="Lukic-input Lukic-input--slug">
									<span class="Lukic-url-suffix">/</span>
								</div>
								<p class="description">
									<?php esc_html_e( 'Enter the URL path for your custom login page (e.g., "admin-login" will create a login page at', 'lukic-code-snippets' ); ?> 
									<code><?php echo esc_html( $site_url ); ?>admin-login/</code>).
								</p>
								<p>
									<?php esc_html_e( 'Your current custom login URL is:', 'lukic-code-snippets' ); ?> 
									<a href="<?php echo esc_url( $login_url ); ?>" target="_blank"><?php echo esc_html( $login_url ); ?></a>
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
	 * Get the custom login slug
	 */
	private function get_login_slug() {
		$options = get_option( $this->option_name, array( 'slug' => 'login' ) );
		return isset( $options['slug'] ) ? $options['slug'] : 'login';
	}

	/**
	 * Get the new login URL
	 */
	private function get_login_url( $scheme = null ) {
		$login_slug = $this->get_login_slug();

		if ( get_option( 'permalink_structure' ) ) {
			return home_url( '/' . $login_slug . '/', $scheme );
		} else {
			return home_url( '/', $scheme ) . '?' . $login_slug;
		}
	}

	/**
	 * Handle login page
	 */
	public function handle_login_page() {
		global $pagenow;

		// Get the request
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
		$request     = wp_parse_url( rawurldecode( $request_uri ) );
		$login_slug  = $this->get_login_slug();

		// Check if we're on the login page
		if ( ( $pagenow === 'wp-login.php' || strpos( $request_uri, 'wp-login.php' ) !== false ) && ! is_admin() ) {
			// Don't redirect for specific actions
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$action = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : '';
			if ( in_array( $action, array( 'logout', 'lostpassword', 'rp', 'resetpass', 'postpass' ) ) ) {
				return;
			}

			// Don't redirect POST requests (form submissions)
			$request_method = isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '';
			if ( $request_method === 'POST' ) {
				return;
			}

			// Redirect to home page
			wp_safe_redirect( home_url() );
			exit;
		}

		// Check if we're on the custom login page
		if ( isset( $request['path'] ) && untrailingslashit( $request['path'] ) === home_url( $login_slug, 'relative' ) ) {
			// Set the page to wp-login.php
			$pagenow = 'wp-login.php';

			// Set up necessary global variables for wp-login.php
			global $error, $interim_login, $action, $user_login;

			// Initialize variables that wp-login.php expects
			$error         = '';
			$user_login    = '';
			$interim_login = false;
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$action        = isset( $_REQUEST['action'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['action'] ) ) : '';

			// Set redirect_to parameter
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( ! isset( $_REQUEST['redirect_to'] ) ) {
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$_REQUEST['redirect_to'] = admin_url();
			}

			// Include the login form
			require_once ABSPATH . 'wp-login.php';
			exit;
		}
	}

	/**
	 * Block access to wp-admin for non-logged in users
	 */
	public function block_wp_admin() {
		global $pagenow;

		// Get the request
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
		$request     = wp_parse_url( rawurldecode( $request_uri ) );

		// Check if we're in wp-admin and not logged in
		if ( is_admin() && ! is_user_logged_in() && ! defined( 'DOING_AJAX' ) && ! defined( 'DOING_CRON' ) ) {
			// Don't block admin-post.php or admin-ajax.php
			if ( $pagenow === 'admin-post.php' || $pagenow === 'admin-ajax.php' ) {
				return;
			}

			// Don't block POST requests (form submissions)
			$request_method = isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '';
			if ( $request_method === 'POST' ) {
				return;
			}

			// Redirect to home page
			wp_safe_redirect( home_url() );
			exit;
		}
	}

	/**
	 * Filter site URL to replace wp-login.php with custom login URL
	 */
	public function filter_site_url( $url, $path, $scheme, $blog_id ) {
		return $this->filter_login_url( $url, $scheme );
	}

	/**
	 * Filter network site URL to replace wp-login.php with custom login URL
	 */
	public function filter_network_site_url( $url, $path, $scheme ) {
		return $this->filter_login_url( $url, $scheme );
	}

	/**
	 * Filter redirects to replace wp-login.php with custom login URL
	 */
	public function filter_wp_redirect( $location, $status ) {
		return $this->filter_login_url( $location );
	}

	/**
	 * Filter URLs to replace wp-login.php with custom login URL
	 */
	private function filter_login_url( $url, $scheme = null ) {
		// Don't modify URLs for password protected posts
		if ( strpos( $url, 'wp-login.php?action=postpass' ) !== false ) {
			return $url;
		}

		// Replace wp-login.php with custom login URL
		if ( strpos( $url, 'wp-login.php' ) !== false ) {
			$args = explode( '?', $url );

			if ( isset( $args[1] ) ) {
				parse_str( $args[1], $args );
				$url = add_query_arg( $args, $this->get_login_url( $scheme ) );
			} else {
				$url = $this->get_login_url( $scheme );
			}
		}

		return $url;
	}

	/**
	 * Filter login URL for WordPress functions
	 */
	public function custom_login_url( $login_url, $redirect, $force_reauth ) {
		$login_url = $this->get_login_url();

		if ( ! empty( $redirect ) ) {
			$login_url = add_query_arg( 'redirect_to', urlencode( $redirect ), $login_url );
		}

		if ( $force_reauth ) {
			$login_url = add_query_arg( 'reauth', '1', $login_url );
		}

		return $login_url;
	}
}

// Initialize the class
new Lukic_Custom_Login_URL();
