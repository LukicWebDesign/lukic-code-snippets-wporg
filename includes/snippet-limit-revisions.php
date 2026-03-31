<?php
/**
 * Snippet: Limit Revisions
 * Description: Prevents database bloat by limiting the number of revisions to keep for post types supporting revisions
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class Lukic_Limit_Revisions
 */
class Lukic_Limit_Revisions {
	/**
	 * The default number of revisions to keep
	 */
	private $default_limit = 5;

	/**
	 * Post type specific revision limits
	 */
	private $post_type_limits = array();

	/**
	 * Constructor
	 */
	public function __construct() {
		// Add submenu page
		add_action( 'admin_menu', array( $this, 'add_submenu_page' ) );

		// Enqueue admin assets
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );

		// Register settings
		add_action( 'admin_init', array( $this, 'register_settings' ) );

		// Filter revisions to keep
		add_filter( 'wp_revisions_to_keep', array( $this, 'limit_revisions' ), 10, 2 );

		// Add admin notice about revisions being limited
		add_action( 'admin_notices', array( $this, 'admin_notice' ) );

		// Load saved settings
		$this->load_settings();
	}

	/**
	 * Load saved settings
	 */
	private function load_settings() {
		$options = get_option( 'Lukic_limit_revisions_options', array() );

		// Set default limit if available
		if ( isset( $options['default_limit'] ) && is_numeric( $options['default_limit'] ) ) {
			$this->default_limit = intval( $options['default_limit'] );
		}

		// Set post type specific limits if available
		if ( isset( $options['post_type_limits'] ) && is_array( $options['post_type_limits'] ) ) {
			$this->post_type_limits = $options['post_type_limits'];
		}
	}

	/**
	 * Add submenu page
	 */
	public function add_submenu_page() {
		add_submenu_page(
			'lukic-code-snippets',
			__( 'Limit Revisions', 'lukic-code-snippets' ),
			__( 'Limit Revisions', 'lukic-code-snippets' ),
			'manage_options',
			'lukic-limit-revisions',
			array( $this, 'display_settings_page' )
		);
	}

	/**
	 * Enqueue admin assets
	 */
	public function enqueue_admin_assets( $hook ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['page'] ) || 'lukic-limit-revisions' !== sanitize_text_field( wp_unslash( $_GET['page'] ) ) ) {
			return;
		}

		wp_add_inline_style( 'Lukic-admin-styles', '
			.Lukic-settings-section { background: #fff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05); padding: 20px; margin-bottom: 20px; }
			.Lukic-settings-section h3 { margin-top: 0; border-bottom: 1px solid #f0f0f0; padding-bottom: 10px; margin-bottom: 15px; }
			.Lukic-settings-field { margin-bottom: 15px; }
			.Lukic-settings-field label { display: block; font-weight: 600; margin-bottom: 5px; }
			.Lukic-limit-revisions-table { width: 100%; border-collapse: collapse; }
			.Lukic-limit-revisions-table th, .Lukic-limit-revisions-table td { padding: 10px; text-align: left; border-bottom: 1px solid #f0f0f0; }
			.Lukic-limit-revisions-table th { background: #f9f9f9; }
			.Lukic-notice { background: #f9f9f9; border-left: 4px solid #00E1AF; padding: 10px 15px; margin: 10px 0; }
			input[type="number"] { width: 80px; }
			.description { color: #666; font-style: italic; margin-left: 10px; }
		' );
	}

	/**
	 * Register settings
	 */
	public function register_settings() {
		register_setting(
			'Lukic_limit_revisions',
			'Lukic_limit_revisions_options',
			array( $this, 'sanitize_settings' )
		);
	}

	/**
	 * Sanitize settings
	 */
	public function sanitize_settings( $input ) {
		$sanitized_input = array();

		// Sanitize default limit
		if ( isset( $input['default_limit'] ) ) {
			$sanitized_input['default_limit'] = intval( $input['default_limit'] );

			// Ensure the limit is at least 1
			if ( $sanitized_input['default_limit'] < 1 ) {
				$sanitized_input['default_limit'] = 1;
			}
		} else {
			$sanitized_input['default_limit'] = $this->default_limit;
		}

		// Sanitize post type specific limits
		$sanitized_input['post_type_limits'] = array();
		if ( isset( $input['post_type_limits'] ) && is_array( $input['post_type_limits'] ) ) {
			foreach ( $input['post_type_limits'] as $post_type => $limit ) {
				if ( post_type_supports( $post_type, 'revisions' ) ) {
					$sanitized_limit = intval( $limit );

					// Ensure the limit is at least 1 or -1 to use default
					if ( $sanitized_limit < 1 && $sanitized_limit != -1 ) {
						$sanitized_limit = 1;
					}

					$sanitized_input['post_type_limits'][ $post_type ] = $sanitized_limit;
				}
			}
		}

		return $sanitized_input;
	}

	/**
	 * Display settings page
	 */
	public function display_settings_page() {
		// Include the header partial
		// Header component is already loaded in main plugin file

		// Prepare stats for header
		$stats = array(
			array(
				'count' => count( $this->post_type_limits ),
				'label' => 'Post Types',
			),
			array(
				'count' => $this->default_limit,
				'label' => 'Default Limit',
			),
		);

		// Get post types that support revisions
		$post_types          = get_post_types( array( 'public' => true ), 'objects' );
		$revision_post_types = array();

		foreach ( $post_types as $post_type ) {
			if ( post_type_supports( $post_type->name, 'revisions' ) ) {
				$revision_post_types[ $post_type->name ] = $post_type->label;
			}
		}

		?>
		<div class="wrap Lukic-settings-wrap">
			<?php Lukic_display_header( __( 'Limit Revisions', 'lukic-code-snippets' ), $stats ); ?>
			
			<div class="Lukic-settings-intro">
				<p><?php esc_html_e( 'Prevent database bloat by limiting the number of revisions to keep for post types supporting revisions.', 'lukic-code-snippets' ); ?></p>
			</div>
			
			<div class="Lukic-settings-container">
				<form method="post" action="options.php">
					<?php settings_fields( 'Lukic_limit_revisions' ); ?>
					
					<div class="Lukic-settings-section">
						<h3><?php esc_html_e( 'Default Revision Limit', 'lukic-code-snippets' ); ?></h3>
						<p><?php esc_html_e( 'Set the default number of revisions to keep for all post types. This will apply to any post type that does not have a specific limit set below.', 'lukic-code-snippets' ); ?></p>
						
						<div class="Lukic-settings-field">
							<label for="default-limit"><?php esc_html_e( 'Default Limit:', 'lukic-code-snippets' ); ?></label>
							<input type="number" id="default-limit" name="Lukic_limit_revisions_options[default_limit]" value="<?php echo esc_attr( $this->default_limit ); ?>" min="1" step="1" />
							<p class="description"><?php esc_html_e( 'Enter the number of revisions to keep by default (minimum: 1).', 'lukic-code-snippets' ); ?></p>
						</div>
					</div>
					
					<div class="Lukic-settings-section">
						<h3><?php esc_html_e( 'Post Type Specific Limits', 'lukic-code-snippets' ); ?></h3>
						<p><?php esc_html_e( 'Set specific revision limits for each post type. Enter -1 to use the default limit.', 'lukic-code-snippets' ); ?></p>
						
						<?php if ( ! empty( $revision_post_types ) ) : ?>
							<table class="form-table Lukic-limit-revisions-table">
								<thead>
									<tr>
										<th><?php esc_html_e( 'Post Type', 'lukic-code-snippets' ); ?></th>
										<th><?php esc_html_e( 'Revision Limit', 'lukic-code-snippets' ); ?></th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ( $revision_post_types as $post_type => $label ) : ?>
										<?php
										$limit = isset( $this->post_type_limits[ $post_type ] ) ? $this->post_type_limits[ $post_type ] : -1;
										?>
										<tr>
											<td>
												<label for="limit-<?php echo esc_attr( $post_type ); ?>"><?php echo esc_html( $label ); ?></label>
											</td>
											<td>
												<input type="number" id="limit-<?php echo esc_attr( $post_type ); ?>" name="Lukic_limit_revisions_options[post_type_limits][<?php echo esc_attr( $post_type ); ?>]" value="<?php echo esc_attr( $limit ); ?>" min="-1" step="1" />
												<span class="description">
													<?php if ( $limit == -1 ) : ?>
														<?php
														/* translators: %d: The default revision limit number */
								echo esc_html( sprintf( __( 'Using default (%d)', 'lukic-code-snippets' ), $this->default_limit ) );
														?>
													<?php endif; ?>
												</span>
											</td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						<?php else : ?>
							<p><?php esc_html_e( 'No post types with revision support found.', 'lukic-code-snippets' ); ?></p>
						<?php endif; ?>
					</div>
					
					<div class="Lukic-settings-section">
						<h3><?php esc_html_e( 'Important Notes', 'lukic-code-snippets' ); ?></h3>
						<div class="Lukic-notice">
							<p><?php esc_html_e( 'This snippet only affects new revisions going forward. It does not automatically delete existing revisions.', 'lukic-code-snippets' ); ?></p>
							<p><?php esc_html_e( 'To clean up existing revisions, you can use a database optimization plugin or run a SQL query to remove old revisions.', 'lukic-code-snippets' ); ?></p>
						</div>
					</div>
					
					<?php submit_button( __( 'Save Settings', 'lukic-code-snippets' ), 'primary', 'submit', true, array( 'style' => 'background-color: #00E1AF; border-color: #00E1AF;' ) ); ?>
				</form>
			</div>
		</div>
		<?php
	}

	/**
	 * Limit revisions
	 *
	 * @param int     $num     Number of revisions to keep
	 * @param WP_Post $post The post object
	 * @return int Modified number of revisions to keep
	 */
	public function limit_revisions( $num, $post ) {
		// Check if we have a specific limit for this post type
		if ( isset( $this->post_type_limits[ $post->post_type ] ) && $this->post_type_limits[ $post->post_type ] > 0 ) {
			return $this->post_type_limits[ $post->post_type ];
		}

		// Otherwise use the default limit
		return $this->default_limit;
	}

	/**
	 * Display admin notice about revisions being limited
	 */
	public function admin_notice() {
		$screen = get_current_screen();

		// Only show on post edit screens for post types that support revisions
		if ( ! $screen || $screen->base !== 'post' || ! post_type_supports( $screen->post_type, 'revisions' ) ) {
			return;
		}

		// Get the limit for this post type
		$limit = isset( $this->post_type_limits[ $screen->post_type ] ) && $this->post_type_limits[ $screen->post_type ] > 0
				? $this->post_type_limits[ $screen->post_type ]
				: $this->default_limit;

		// Get the post type label
		$post_type_obj   = get_post_type_object( $screen->post_type );
		$post_type_label = $post_type_obj ? $post_type_obj->labels->singular_name : $screen->post_type;

		?>
		<div class="notice notice-info is-dismissible">
			<p>
				<?php
				echo esc_html( sprintf(
					/* translators: %1$s: Post type name (e.g., Post, Page), %2$d: Revision limit number */
					__( 'Revisions for this %1$s are limited to %2$d. Older revisions will be automatically deleted.', 'lukic-code-snippets' ),
					$post_type_label,
					intval( $limit )
				) );
				?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=lukic-limit-revisions' ) ); ?>">
					<?php esc_html_e( 'Configure revision limits', 'lukic-code-snippets' ); ?>
				</a>
			</p>
		</div>
		<?php
	}
}

// Initialize the snippet
new Lukic_Limit_Revisions();
