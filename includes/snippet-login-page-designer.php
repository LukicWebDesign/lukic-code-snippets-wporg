<?php
/**
 * Login Page Designer snippet
 *
 * Allows full visual customization of the WordPress login page:
 * logo, background, form card, colors, and button styling.
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class Lukic_Login_Page_Designer
 */
class Lukic_Login_Page_Designer {

	/**
	 * Option name used in the database.
	 *
	 * @var string
	 */
	private $option_name = 'Lukic_login_page_designer_options';

	/**
	 * Default settings.
	 *
	 * @var array
	 */
	private $defaults;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->defaults = self::get_default_settings();

		// Admin menu.
		add_action( 'admin_menu', array( $this, 'add_submenu_page' ) );

		// Register settings.
		add_action( 'admin_init', array( $this, 'register_settings' ) );

		// Admin assets (loaded only on our settings page).
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

		// Inject styles into the login page.
		add_action( 'login_enqueue_scripts', array( $this, 'inject_login_styles' ) );
	}

	// -------------------------------------------------------------------------
	// Default settings
	// -------------------------------------------------------------------------

	/**
	 * Returns the default settings array.
	 *
	 * @return array
	 */
	public static function get_default_settings() {
		return array(
			// Logo
			'logo_url'           => '',
			'logo_width'         => 80,
			'logo_link_url'      => '',
			'logo_alt_text'      => '',

			// Background
			'bg_type'            => 'color',  // color | image | gradient
			'bg_color'           => '#f0f0f1',
			'bg_image'           => '',
			'bg_position'        => 'center center',
			'bg_size'            => 'cover',
			'bg_gradient_from'   => '#667eea',
			'bg_gradient_to'     => '#764ba2',
			'bg_gradient_angle'  => 135,

			// Form card
			'card_bg_color'      => '#ffffff',
			'card_border_radius' => 4,
			'card_shadow'        => 'soft',  // none | soft | strong

			// Colors
			'label_color'        => '#3c4858',
			'link_color'         => '#2271b1',
			'link_hover_color'   => '#135e96',
			'nav_color'          => '#50575e',
			'backtoblog_color'   => '#50575e',

			// Button
			'btn_bg_color'       => '#2271b1',
			'btn_text_color'     => '#ffffff',
			'btn_hover_bg_color' => '#135e96',
			'btn_border_radius'  => 3,

			// Custom CSS
			'custom_css'         => '',
		);
	}

	// -------------------------------------------------------------------------
	// Admin menu
	// -------------------------------------------------------------------------

	/**
	 * Add submenu page under the main plugin menu.
	 */
	public function add_submenu_page() {
		add_submenu_page(
			'lukic-code-snippets',
			__( 'Login Page Designer', 'lukic-code-snippets' ),
			__( 'Login Page Designer', 'lukic-code-snippets' ),
			'manage_options',
			'lukic-login-page-designer',
			array( $this, 'display_settings_page' )
		);
	}

	// -------------------------------------------------------------------------
	// Settings registration & sanitization
	// -------------------------------------------------------------------------

	/**
	 * Register the settings group.
	 */
	public function register_settings() {
		register_setting(
			'Lukic_login_page_designer_group',
			$this->option_name,
			array( $this, 'sanitize_settings' )
		);
	}

	/**
	 * Sanitize settings on save.
	 *
	 * @param array $input Raw POST input.
	 * @return array Sanitized values.
	 */
	public function sanitize_settings( $input ) {
		if ( ! is_array( $input ) ) {
			$input = array();
		}

		$out = array();

		// Logo
		$out['logo_url']      = isset( $input['logo_url'] ) ? esc_url_raw( wp_unslash( $input['logo_url'] ) ) : '';
		$out['logo_width']    = isset( $input['logo_width'] ) ? max( 40, min( 320, absint( $input['logo_width'] ) ) ) : $this->defaults['logo_width'];
		$out['logo_link_url'] = isset( $input['logo_link_url'] ) ? esc_url_raw( wp_unslash( $input['logo_link_url'] ) ) : '';
		$out['logo_alt_text'] = isset( $input['logo_alt_text'] ) ? sanitize_text_field( wp_unslash( $input['logo_alt_text'] ) ) : '';

		// Background
		$bg_types      = array( 'color', 'image', 'gradient' );
		$bg_type       = isset( $input['bg_type'] ) ? sanitize_key( wp_unslash( $input['bg_type'] ) ) : '';
		$out['bg_type'] = in_array( $bg_type, $bg_types, true )
			? $bg_type
			: $this->defaults['bg_type'];

		$out['bg_color']          = $this->sanitize_color( $input['bg_color'] ?? $this->defaults['bg_color'] );
		$out['bg_image']          = isset( $input['bg_image'] ) ? esc_url_raw( wp_unslash( $input['bg_image'] ) ) : '';
		$out['bg_gradient_from']  = $this->sanitize_color( $input['bg_gradient_from'] ?? $this->defaults['bg_gradient_from'] );
		$out['bg_gradient_to']    = $this->sanitize_color( $input['bg_gradient_to'] ?? $this->defaults['bg_gradient_to'] );
		$out['bg_gradient_angle'] = isset( $input['bg_gradient_angle'] ) ? max( 0, min( 360, absint( $input['bg_gradient_angle'] ) ) ) : $this->defaults['bg_gradient_angle'];

		$bg_positions         = array( 'center center', 'top center', 'bottom center', 'center left', 'center right' );
		$bg_position          = isset( $input['bg_position'] ) ? sanitize_text_field( wp_unslash( $input['bg_position'] ) ) : '';
		$out['bg_position']   = in_array( $bg_position, $bg_positions, true )
			? $bg_position
			: $this->defaults['bg_position'];

		$bg_sizes       = array( 'cover', 'contain', 'auto' );
		$bg_size        = isset( $input['bg_size'] ) ? sanitize_key( wp_unslash( $input['bg_size'] ) ) : '';
		$out['bg_size'] = in_array( $bg_size, $bg_sizes, true )
			? $bg_size
			: $this->defaults['bg_size'];

		// Form card
		$out['card_bg_color']      = $this->sanitize_color( $input['card_bg_color'] ?? $this->defaults['card_bg_color'] );
		$out['card_border_radius'] = isset( $input['card_border_radius'] ) ? min( 40, absint( $input['card_border_radius'] ) ) : $this->defaults['card_border_radius'];
		$shadow_options            = array( 'none', 'soft', 'strong' );
		$card_shadow               = isset( $input['card_shadow'] ) ? sanitize_key( wp_unslash( $input['card_shadow'] ) ) : '';
		$out['card_shadow']        = in_array( $card_shadow, $shadow_options, true )
			? $card_shadow
			: $this->defaults['card_shadow'];

		// Colors
		$out['label_color']      = $this->sanitize_color( $input['label_color'] ?? $this->defaults['label_color'] );
		$out['link_color']       = $this->sanitize_color( $input['link_color'] ?? $this->defaults['link_color'] );
		$out['link_hover_color'] = $this->sanitize_color( $input['link_hover_color'] ?? $this->defaults['link_hover_color'] );
		$out['nav_color']        = $this->sanitize_color( $input['nav_color'] ?? $this->defaults['nav_color'] );
		$out['backtoblog_color'] = $this->sanitize_color( $input['backtoblog_color'] ?? $this->defaults['backtoblog_color'] );

		// Button
		$out['btn_bg_color']       = $this->sanitize_color( $input['btn_bg_color'] ?? $this->defaults['btn_bg_color'] );
		$out['btn_text_color']     = $this->sanitize_color( $input['btn_text_color'] ?? $this->defaults['btn_text_color'] );
		$out['btn_hover_bg_color'] = $this->sanitize_color( $input['btn_hover_bg_color'] ?? $this->defaults['btn_hover_bg_color'] );
		$out['btn_border_radius']  = isset( $input['btn_border_radius'] ) ? min( 50, absint( $input['btn_border_radius'] ) ) : $this->defaults['btn_border_radius'];

		// Custom CSS is disabled in the wp.org-safe build.
		$out['custom_css'] = '';

		return $out;
	}

	/**
	 * Validate a color value — accepts hex or rgba().
	 *
	 * @param string $color
	 * @return string Sanitized color or empty string.
	 */
	private function sanitize_color( $color ) {
		$color = is_string( $color ) ? wp_unslash( $color ) : '';
		$color = trim( $color );
		// Hex
		if ( preg_match( '/^#([a-fA-F0-9]{3}){1,2}$/', $color ) ) {
			return $color;
		}
		// rgba
		if ( preg_match( '/^rgba?\(\s*\d+\s*,\s*\d+\s*,\s*\d+\s*(?:,\s*[\d.]+\s*)?\)$/', $color ) ) {
			return $color;
		}
		return '';
	}

	// -------------------------------------------------------------------------
	// Asset enqueueing
	// -------------------------------------------------------------------------

	/**
	 * Enqueue scripts & styles for the admin settings page only.
	 *
	 * @param string $hook Current page hook.
	 */
	public function enqueue_admin_scripts( $hook ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['page'] ) || 'lukic-login-page-designer' !== sanitize_key( wp_unslash( $_GET['page'] ) ) ) {
			return;
		}

		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );
		wp_enqueue_media();

		wp_enqueue_script(
			'lukic-login-page-designer-admin',
			plugin_dir_url( __DIR__ ) . 'assets/js/login-page-designer-admin.js',
			array( 'jquery', 'wp-color-picker' ),
			Lukic_SNIPPET_CODES_VERSION,
			true
		);

		wp_enqueue_style(
			'lukic-login-page-designer-admin',
			plugin_dir_url( __DIR__ ) . 'assets/css/login-page-designer-admin.css',
			array(),
			Lukic_SNIPPET_CODES_VERSION . '.' . time()
		);
	}

	// -------------------------------------------------------------------------
	// Login page style injection
	// -------------------------------------------------------------------------

	/**
	 * Enqueue custom CSS for the login page.
	 */
	public function inject_login_styles() {
		$opts = $this->sanitize_settings(
			wp_parse_args(
				get_option( $this->option_name, array() ),
				$this->defaults
			)
		);

		// Build the background declaration
		$background_css = $this->build_background_css( $opts );

		// Box shadow for the card
		$shadow_map = array(
			'none'   => 'none',
			'soft'   => '0 4px 24px rgba(0,0,0,0.10)',
			'strong' => '0 8px 40px rgba(0,0,0,0.28)',
		);
		$card_shadow = $shadow_map[ $opts['card_shadow'] ] ?? $shadow_map['soft'];

		// Logo width (min 40, max 320)
		$logo_width = max( 40, min( 320, (int) $opts['logo_width'] ) );

		$css  = "body.login {\n" . $background_css . "\n}\n";
		$css .= "#login h1 a,\n.login h1 a {\n";
		if ( ! empty( $opts['logo_url'] ) ) {
			$css .= "background-image: url('" . esc_url_raw( $opts['logo_url'] ) . "');\n";
			$css .= "background-size: contain;\n";
			$css .= "background-repeat: no-repeat;\n";
			$css .= "background-position: center bottom;\n";
			$css .= 'width: ' . absint( $logo_width ) . "px;\n";
			$css .= 'height: ' . absint( $logo_width ) . "px;\n";
		}
		$css .= "}\n";
		$css .= "#login,\n.login .wp-login-logo + * {\n\tbackground: transparent;\n}\n";
		$css .= "#loginform,\n#lostpasswordform,\n#registerform {\n";
		$css .= "\tbackground-color: {$opts['card_bg_color']};\n";
		$css .= "\tborder-radius: " . absint( $opts['card_border_radius'] ) . "px;\n";
		$css .= "\tbox-shadow: {$card_shadow};\n";
		$css .= "\tborder: none;\n}\n";
		$css .= ".login label {\n\tcolor: {$opts['label_color']};\n}\n";
		$css .= ".login #nav a,\n.login #backtoblog a {\n\tcolor: {$opts['link_color']};\n}\n";
		$css .= ".login #nav a:hover,\n.login #backtoblog a:hover {\n\tcolor: {$opts['link_hover_color']};\n}\n";
		$css .= "#nav a {\n\tcolor: {$opts['nav_color']} !important;\n}\n";
		$css .= "#backtoblog a {\n\tcolor: {$opts['backtoblog_color']} !important;\n}\n";
		$css .= ".login .button-primary,\n#wp-submit {\n";
		$css .= "\tbackground-color: {$opts['btn_bg_color']} !important;\n";
		$css .= "\tborder-color: {$opts['btn_bg_color']} !important;\n";
		$css .= "\tcolor: {$opts['btn_text_color']} !important;\n";
		$css .= "\tborder-radius: " . absint( $opts['btn_border_radius'] ) . "px !important;\n";
		$css .= "\tbox-shadow: none !important;\n";
		$css .= "\ttext-shadow: none !important;\n}\n";
		$css .= ".login .button-primary:hover,\n#wp-submit:hover {\n";
		$css .= "\tbackground-color: {$opts['btn_hover_bg_color']} !important;\n";
		$css .= "\tborder-color: {$opts['btn_hover_bg_color']} !important;\n}\n";

		wp_register_style( 'lukic-login-designer', false, array(), Lukic_SNIPPET_CODES_VERSION );
		wp_enqueue_style( 'lukic-login-designer' );
		wp_add_inline_style( 'lukic-login-designer', $css );

		// Override logo link & title if set
		if ( ! empty( $opts['logo_link_url'] ) || ! empty( $opts['logo_alt_text'] ) ) {
			add_filter( 'login_headerurl', function() use ( $opts ) {
				return ! empty( $opts['logo_link_url'] ) ? esc_url_raw( $opts['logo_link_url'] ) : home_url();
			} );
			add_filter( 'login_headertext', function() use ( $opts ) {
				return ! empty( $opts['logo_alt_text'] ) ? sanitize_text_field( $opts['logo_alt_text'] ) : get_bloginfo( 'name' );
			} );
		}
	}

	/**
	 * Build the CSS background properties based on bg_type.
	 *
	 * @param array $opts Settings.
	 * @return string CSS declarations for wp_add_inline_style().
	 */
	private function build_background_css( $opts ) {
		$css = '';

		switch ( $opts['bg_type'] ) {
			case 'image':
				if ( ! empty( $opts['bg_image'] ) ) {
					$css .= 'background-image: url("' . esc_url_raw( $opts['bg_image'] ) . '");';
					$css .= 'background-position: ' . $opts['bg_position'] . ';';
					$css .= 'background-size: ' . $opts['bg_size'] . ';';
					$css .= 'background-repeat: no-repeat;';
				} else {
					$css .= 'background-color: ' . $opts['bg_color'] . ';';
				}
				break;

			case 'gradient':
				$angle = absint( $opts['bg_gradient_angle'] );
				$from  = $opts['bg_gradient_from'];
				$to    = $opts['bg_gradient_to'];
				$css  .= 'background: linear-gradient(' . $angle . 'deg, ' . $from . ', ' . $to . ');';
				break;

			case 'color':
			default:
				$css .= 'background-color: ' . $opts['bg_color'] . ';';
				break;
		}

		return $css;
	}

	// -------------------------------------------------------------------------
	// Admin settings page
	// -------------------------------------------------------------------------

	/**
	 * Render the settings page.
	 */
	public function display_settings_page() {
		$opts  = $this->sanitize_settings( wp_parse_args( get_option( $this->option_name, array() ), $this->defaults ) );
		$stats = array(
			array(
				'count' => ucfirst( $opts['bg_type'] ),
				'label' => __( 'Background', 'lukic-code-snippets' ),
			),
			array(
				'count' => absint( $opts['logo_width'] ) . 'px',
				'label' => __( 'Logo Width', 'lukic-code-snippets' ),
			),
		);
		?>
		<div class="wrap Lukic-settings-wrap">
			<?php Lukic_display_header( __( 'Login Page Designer', 'lukic-code-snippets' ), $stats ); ?>

			<div class="Lukic-settings-intro">
				<p><?php esc_html_e( 'Customize the appearance of the WordPress login page. Changes apply immediately after saving — open your login page in an incognito window to preview.', 'lukic-code-snippets' ); ?></p>
			</div>

			<div class="Lukic-settings-container lpd-layout">
				<!-- ── Main form ── -->
				<div class="Lukic-settings-main">
					<form method="post" action="options.php" id="lpd-form">
						<?php settings_fields( 'Lukic_login_page_designer_group' ); ?>
						<?php settings_errors(); ?>

						<!-- 1. Logo & Branding -->
						<div class="Lukic-settings-section">
							<h2><?php esc_html_e( 'Logo & Branding', 'lukic-code-snippets' ); ?></h2>

							<div class="Lukic-field-row">
								<label><?php esc_html_e( 'Logo Image', 'lukic-code-snippets' ); ?></label>
								<div class="lpd-media-wrap">
									<input type="hidden"
										id="lpd_logo_url"
										name="<?php echo esc_attr( $this->option_name ); ?>[logo_url]"
										value="<?php echo esc_attr( $opts['logo_url'] ); ?>">
									<div class="lpd-image-preview" id="lpd_logo_preview">
										<?php if ( ! empty( $opts['logo_url'] ) ) : ?>
											<img src="<?php echo esc_url( $opts['logo_url'] ); ?>" alt="">
										<?php endif; ?>
									</div>
									<div class="lpd-media-btns">
										<button type="button" class="button lpd-upload-btn" data-target="lpd_logo_url" data-preview="lpd_logo_preview">
											<?php esc_html_e( 'Upload / Select Logo', 'lukic-code-snippets' ); ?>
										</button>
										<button type="button" class="button lpd-remove-btn" data-target="lpd_logo_url" data-preview="lpd_logo_preview" <?php echo empty( $opts['logo_url'] ) ? 'style="display:none"' : ''; ?>>
											<?php esc_html_e( 'Remove', 'lukic-code-snippets' ); ?>
										</button>
									</div>
									<p class="description"><?php esc_html_e( 'Replaces the WordPress logo on the login screen. PNG/SVG with transparent background recommended.', 'lukic-code-snippets' ); ?></p>
								</div>
							</div>

							<div class="Lukic-grid-2-col">
								<div class="Lukic-field-row">
									<label for="lpd_logo_width"><?php esc_html_e( 'Logo Width (px)', 'lukic-code-snippets' ); ?></label>
									<input type="number" id="lpd_logo_width"
										name="<?php echo esc_attr( $this->option_name ); ?>[logo_width]"
										value="<?php echo esc_attr( $opts['logo_width'] ); ?>"
										min="40" max="320" class="small-text">
								</div>
								<div class="Lukic-field-row">
									<label for="lpd_logo_alt_text"><?php esc_html_e( 'Logo Alt Text', 'lukic-code-snippets' ); ?></label>
									<input type="text" id="lpd_logo_alt_text"
										name="<?php echo esc_attr( $this->option_name ); ?>[logo_alt_text]"
										value="<?php echo esc_attr( $opts['logo_alt_text'] ); ?>"
										class="regular-text">
								</div>
							</div>

							<div class="Lukic-field-row">
								<label for="lpd_logo_link_url"><?php esc_html_e( 'Logo Link URL', 'lukic-code-snippets' ); ?></label>
								<input type="url" id="lpd_logo_link_url"
									name="<?php echo esc_attr( $this->option_name ); ?>[logo_link_url]"
									value="<?php echo esc_attr( $opts['logo_link_url'] ); ?>"
									class="regular-text"
									placeholder="<?php echo esc_attr( home_url() ); ?>">
								<p class="description"><?php esc_html_e( 'URL when the logo is clicked. Defaults to site homepage.', 'lukic-code-snippets' ); ?></p>
							</div>
						</div>

						<!-- 2. Background -->
						<div class="Lukic-settings-section">
							<h2><?php esc_html_e( 'Background', 'lukic-code-snippets' ); ?></h2>

							<div class="Lukic-field-row">
								<label><?php esc_html_e( 'Background Type', 'lukic-code-snippets' ); ?></label>
								<div class="lpd-type-toggle" id="lpd_bg_type_toggle">
									<?php
									$bg_types = array(
										'color'    => __( 'Color', 'lukic-code-snippets' ),
										'image'    => __( 'Image', 'lukic-code-snippets' ),
										'gradient' => __( 'Gradient', 'lukic-code-snippets' ),
									);
									foreach ( $bg_types as $value => $label ) :
									?>
									<label class="lpd-type-label <?php echo esc_attr( $opts['bg_type'] === $value ? 'is-active' : '' ); ?>">
										<input type="radio"
											name="<?php echo esc_attr( $this->option_name ); ?>[bg_type]"
											value="<?php echo esc_attr( $value ); ?>"
											<?php checked( $opts['bg_type'], $value ); ?>>
										<?php echo esc_html( $label ); ?>
									</label>
									<?php endforeach; ?>
								</div>
							</div>

							<!-- Color sub-section -->
							<div class="lpd-bg-sub" id="lpd_bg_sub_color" <?php echo $opts['bg_type'] !== 'color' ? 'style="display:none"' : ''; ?>>
								<div class="Lukic-field-row">
									<label for="lpd_bg_color"><?php esc_html_e( 'Background Color', 'lukic-code-snippets' ); ?></label>
									<input type="text" id="lpd_bg_color"
										name="<?php echo esc_attr( $this->option_name ); ?>[bg_color]"
										value="<?php echo esc_attr( $opts['bg_color'] ); ?>"
										class="Lukic-color-picker">
								</div>
							</div>

							<!-- Image sub-section -->
							<div class="lpd-bg-sub" id="lpd_bg_sub_image" <?php echo $opts['bg_type'] !== 'image' ? 'style="display:none"' : ''; ?>>
								<div class="Lukic-field-row">
									<label><?php esc_html_e( 'Background Image', 'lukic-code-snippets' ); ?></label>
									<div class="lpd-media-wrap">
										<input type="hidden"
											id="lpd_bg_image"
											name="<?php echo esc_attr( $this->option_name ); ?>[bg_image]"
											value="<?php echo esc_attr( $opts['bg_image'] ); ?>">
										<div class="lpd-image-preview lpd-bg-preview" id="lpd_bg_image_preview">
											<?php if ( ! empty( $opts['bg_image'] ) ) : ?>
												<img src="<?php echo esc_url( $opts['bg_image'] ); ?>" alt="">
											<?php endif; ?>
										</div>
										<div class="lpd-media-btns">
											<button type="button" class="button lpd-upload-btn" data-target="lpd_bg_image" data-preview="lpd_bg_image_preview">
												<?php esc_html_e( 'Upload / Select Image', 'lukic-code-snippets' ); ?>
											</button>
											<button type="button" class="button lpd-remove-btn" data-target="lpd_bg_image" data-preview="lpd_bg_image_preview" <?php echo empty( $opts['bg_image'] ) ? 'style="display:none"' : ''; ?>>
												<?php esc_html_e( 'Remove', 'lukic-code-snippets' ); ?>
											</button>
										</div>
									</div>
								</div>

								<div class="Lukic-grid-2-col">
									<div class="Lukic-field-row">
										<label for="lpd_bg_position"><?php esc_html_e( 'Position', 'lukic-code-snippets' ); ?></label>
										<select id="lpd_bg_position" name="<?php echo esc_attr( $this->option_name ); ?>[bg_position]" class="Lukic-select">
											<?php
											$positions = array(
												'center center' => __( 'Center Center', 'lukic-code-snippets' ),
												'top center'    => __( 'Top Center', 'lukic-code-snippets' ),
												'bottom center' => __( 'Bottom Center', 'lukic-code-snippets' ),
												'center left'   => __( 'Center Left', 'lukic-code-snippets' ),
												'center right'  => __( 'Center Right', 'lukic-code-snippets' ),
											);
											foreach ( $positions as $val => $label ) {
												printf(
													'<option value="%s" %s>%s</option>',
													esc_attr( $val ),
													selected( $opts['bg_position'], $val, false ),
													esc_html( $label )
												);
											}
											?>
										</select>
									</div>
									<div class="Lukic-field-row">
										<label for="lpd_bg_size"><?php esc_html_e( 'Size', 'lukic-code-snippets' ); ?></label>
										<select id="lpd_bg_size" name="<?php echo esc_attr( $this->option_name ); ?>[bg_size]" class="Lukic-select">
											<?php
											$sizes = array(
												'cover'   => __( 'Cover (fill screen)', 'lukic-code-snippets' ),
												'contain' => __( 'Contain (show whole image)', 'lukic-code-snippets' ),
												'auto'    => __( 'Auto (original size)', 'lukic-code-snippets' ),
											);
											foreach ( $sizes as $val => $label ) {
												printf(
													'<option value="%s" %s>%s</option>',
													esc_attr( $val ),
													selected( $opts['bg_size'], $val, false ),
													esc_html( $label )
												);
											}
											?>
										</select>
									</div>
								</div>
							</div>

							<!-- Gradient sub-section -->
							<div class="lpd-bg-sub" id="lpd_bg_sub_gradient" <?php echo $opts['bg_type'] !== 'gradient' ? 'style="display:none"' : ''; ?>>
								<div class="Lukic-grid-2-col">
									<div class="Lukic-field-row">
										<label for="lpd_bg_gradient_from"><?php esc_html_e( 'From Color', 'lukic-code-snippets' ); ?></label>
										<input type="text" id="lpd_bg_gradient_from"
											name="<?php echo esc_attr( $this->option_name ); ?>[bg_gradient_from]"
											value="<?php echo esc_attr( $opts['bg_gradient_from'] ); ?>"
											class="Lukic-color-picker">
									</div>
									<div class="Lukic-field-row">
										<label for="lpd_bg_gradient_to"><?php esc_html_e( 'To Color', 'lukic-code-snippets' ); ?></label>
										<input type="text" id="lpd_bg_gradient_to"
											name="<?php echo esc_attr( $this->option_name ); ?>[bg_gradient_to]"
											value="<?php echo esc_attr( $opts['bg_gradient_to'] ); ?>"
											class="Lukic-color-picker">
									</div>
								</div>
								<div class="Lukic-field-row">
									<label for="lpd_bg_gradient_angle"><?php esc_html_e( 'Gradient Angle (°)', 'lukic-code-snippets' ); ?></label>
									<input type="number" id="lpd_bg_gradient_angle"
										name="<?php echo esc_attr( $this->option_name ); ?>[bg_gradient_angle]"
										value="<?php echo esc_attr( $opts['bg_gradient_angle'] ); ?>"
										min="0" max="360" class="small-text">
									<p class="description"><?php esc_html_e( '0° = bottom to top, 90° = left to right, 135° = diagonal.', 'lukic-code-snippets' ); ?></p>
								</div>
							</div>
						</div>

						<!-- 3. Login Form Card -->
						<div class="Lukic-settings-section">
							<h2><?php esc_html_e( 'Login Form Card', 'lukic-code-snippets' ); ?></h2>

							<div class="Lukic-grid-2-col">
								<div class="Lukic-field-row">
									<label for="lpd_card_bg_color"><?php esc_html_e( 'Card Background Color', 'lukic-code-snippets' ); ?></label>
									<input type="text" id="lpd_card_bg_color"
										name="<?php echo esc_attr( $this->option_name ); ?>[card_bg_color]"
										value="<?php echo esc_attr( $opts['card_bg_color'] ); ?>"
										class="Lukic-color-picker">
								</div>
								<div class="Lukic-field-row">
									<label for="lpd_card_border_radius"><?php esc_html_e( 'Border Radius (px)', 'lukic-code-snippets' ); ?></label>
									<input type="number" id="lpd_card_border_radius"
										name="<?php echo esc_attr( $this->option_name ); ?>[card_border_radius]"
										value="<?php echo esc_attr( $opts['card_border_radius'] ); ?>"
										min="0" max="40" class="small-text">
								</div>
							</div>

							<div class="Lukic-field-row">
								<label><?php esc_html_e( 'Box Shadow', 'lukic-code-snippets' ); ?></label>
								<div class="lpd-shadow-toggle">
									<?php
									$shadows = array(
										'none'   => __( 'None', 'lukic-code-snippets' ),
										'soft'   => __( 'Soft', 'lukic-code-snippets' ),
										'strong' => __( 'Strong', 'lukic-code-snippets' ),
									);
									foreach ( $shadows as $val => $label ) :
									?>
									<label class="lpd-type-label <?php echo esc_attr( $opts['card_shadow'] === $val ? 'is-active' : '' ); ?>">
										<input type="radio"
											name="<?php echo esc_attr( $this->option_name ); ?>[card_shadow]"
											value="<?php echo esc_attr( $val ); ?>"
											<?php checked( $opts['card_shadow'], $val ); ?>>
										<?php echo esc_html( $label ); ?>
									</label>
									<?php endforeach; ?>
								</div>
							</div>
						</div>

						<!-- 4. Colors & Button -->
						<div class="Lukic-settings-section">
							<h2><?php esc_html_e( 'Colors & Button', 'lukic-code-snippets' ); ?></h2>

							<div class="Lukic-grid-2-col">
								<div class="Lukic-field-row">
									<label for="lpd_label_color"><?php esc_html_e( 'Label Color', 'lukic-code-snippets' ); ?></label>
									<input type="text" id="lpd_label_color"
										name="<?php echo esc_attr( $this->option_name ); ?>[label_color]"
										value="<?php echo esc_attr( $opts['label_color'] ); ?>"
										class="Lukic-color-picker">
								</div>
								<div class="Lukic-field-row">
									<label for="lpd_link_color"><?php esc_html_e( 'Link Color', 'lukic-code-snippets' ); ?></label>
									<input type="text" id="lpd_link_color"
										name="<?php echo esc_attr( $this->option_name ); ?>[link_color]"
										value="<?php echo esc_attr( $opts['link_color'] ); ?>"
										class="Lukic-color-picker">
								</div>
								<div class="Lukic-field-row">
									<label for="lpd_link_hover_color"><?php esc_html_e( 'Link Hover Color', 'lukic-code-snippets' ); ?></label>
									<input type="text" id="lpd_link_hover_color"
										name="<?php echo esc_attr( $this->option_name ); ?>[link_hover_color]"
										value="<?php echo esc_attr( $opts['link_hover_color'] ); ?>"
										class="Lukic-color-picker">
								</div>
								<div class="Lukic-field-row">
									<label for="lpd_nav_color"><?php esc_html_e( '"Lost password?" link color (#nav a)', 'lukic-code-snippets' ); ?></label>
									<input type="text" id="lpd_nav_color"
										name="<?php echo esc_attr( $this->option_name ); ?>[nav_color]"
										value="<?php echo esc_attr( $opts['nav_color'] ); ?>"
										class="Lukic-color-picker">
								</div>
								<div class="Lukic-field-row">
									<label for="lpd_backtoblog_color"><?php esc_html_e( '"← Back to site" link color (#backtoblog a)', 'lukic-code-snippets' ); ?></label>
									<input type="text" id="lpd_backtoblog_color"
										name="<?php echo esc_attr( $this->option_name ); ?>[backtoblog_color]"
										value="<?php echo esc_attr( $opts['backtoblog_color'] ); ?>"
										class="Lukic-color-picker">
								</div>
							</div>

							<h3><?php esc_html_e( 'Submit Button', 'lukic-code-snippets' ); ?></h3>
							<div class="Lukic-grid-2-col">
								<div class="Lukic-field-row">
									<label for="lpd_btn_bg_color"><?php esc_html_e( 'Button Background', 'lukic-code-snippets' ); ?></label>
									<input type="text" id="lpd_btn_bg_color"
										name="<?php echo esc_attr( $this->option_name ); ?>[btn_bg_color]"
										value="<?php echo esc_attr( $opts['btn_bg_color'] ); ?>"
										class="Lukic-color-picker">
								</div>
								<div class="Lukic-field-row">
									<label for="lpd_btn_text_color"><?php esc_html_e( 'Button Text Color', 'lukic-code-snippets' ); ?></label>
									<input type="text" id="lpd_btn_text_color"
										name="<?php echo esc_attr( $this->option_name ); ?>[btn_text_color]"
										value="<?php echo esc_attr( $opts['btn_text_color'] ); ?>"
										class="Lukic-color-picker">
								</div>
								<div class="Lukic-field-row">
									<label for="lpd_btn_hover_bg_color"><?php esc_html_e( 'Button Hover Background', 'lukic-code-snippets' ); ?></label>
									<input type="text" id="lpd_btn_hover_bg_color"
										name="<?php echo esc_attr( $this->option_name ); ?>[btn_hover_bg_color]"
										value="<?php echo esc_attr( $opts['btn_hover_bg_color'] ); ?>"
										class="Lukic-color-picker">
								</div>
								<div class="Lukic-field-row">
									<label for="lpd_btn_border_radius"><?php esc_html_e( 'Button Border Radius (px)', 'lukic-code-snippets' ); ?></label>
									<input type="number" id="lpd_btn_border_radius"
										name="<?php echo esc_attr( $this->option_name ); ?>[btn_border_radius]"
										value="<?php echo esc_attr( $opts['btn_border_radius'] ); ?>"
										min="0" max="50" class="small-text">
								</div>
							</div>
						</div>

						<!-- 5. Custom CSS -->
						<div class="Lukic-settings-section">
							<h2><?php esc_html_e( 'Custom CSS', 'lukic-code-snippets' ); ?></h2>
							<div class="Lukic-field-row">
								<label for="lpd_custom_css"><?php esc_html_e( 'Additional CSS', 'lukic-code-snippets' ); ?></label>
								<textarea id="lpd_custom_css"
									name="<?php echo esc_attr( $this->option_name ); ?>[custom_css_disabled]"
									rows="8"
									class="large-text code"
									disabled="disabled"
									readonly="readonly"
									placeholder="/* Custom CSS is disabled in this build. */"></textarea>
								<p class="description"><?php esc_html_e( 'Arbitrary custom CSS is disabled in this WordPress.org-safe build. Use the structured options above instead.', 'lukic-code-snippets' ); ?></p>
							</div>
						</div>

						<div class="Lukic-submit-container">
							<?php submit_button( __( 'Save Changes', 'lukic-code-snippets' ), 'primary Lukic-btn Lukic-btn--primary', 'submit', false ); ?>
							<a href="<?php echo esc_url( wp_login_url() ); ?>" target="_blank" class="button lpd-preview-btn">
								<?php esc_html_e( '↗ Preview Login Page', 'lukic-code-snippets' ); ?>
							</a>
						</div>
					</form>
				</div>

				<!-- ── Live Preview panel ── -->
				<div class="Lukic-settings-preview">
					<div class="Lukic-settings-section">
						<h2><?php esc_html_e( 'Live Preview', 'lukic-code-snippets' ); ?></h2>
						<div class="lpd-preview-wrap" id="lpd-preview">
							<div class="lpd-preview-bg" id="lpd-preview-bg">
								<div class="lpd-preview-card" id="lpd-preview-card">
									<div class="lpd-preview-logo" id="lpd-preview-logo">
										<?php if ( ! empty( $opts['logo_url'] ) ) : ?>
											<img src="<?php echo esc_url( $opts['logo_url'] ); ?>" alt="">
										<?php else : ?>
											<span class="lpd-preview-logo-placeholder"><?php esc_html_e( 'Logo', 'lukic-code-snippets' ); ?></span>
										<?php endif; ?>
									</div>
									<div class="lpd-preview-field">
										<span class="lpd-preview-label" id="lpd-preview-label"><?php esc_html_e( 'Username', 'lukic-code-snippets' ); ?></span>
										<div class="lpd-preview-input"></div>
									</div>
									<div class="lpd-preview-field">
										<span class="lpd-preview-label" id="lpd-preview-label-2"><?php esc_html_e( 'Password', 'lukic-code-snippets' ); ?></span>
										<div class="lpd-preview-input"></div>
									</div>
									<div class="lpd-preview-btn-wrap">
										<div class="lpd-preview-submit" id="lpd-preview-submit"><?php esc_html_e( 'Log In', 'lukic-code-snippets' ); ?></div>
									</div>
									<div class="lpd-preview-links">
										<a href="#" class="lpd-preview-link" id="lpd-preview-link"><?php esc_html_e( 'Lost your password?', 'lukic-code-snippets' ); ?></a>
									</div>
								</div>
							</div>
						</div>
						<p class="description"><?php esc_html_e( 'Schematic preview — updates in real time as you change settings.', 'lukic-code-snippets' ); ?></p>
					</div>
				</div>
			</div><!-- .Lukic-settings-container -->
		</div><!-- .wrap -->
		<?php
	}
}

// Initialize the class.
new Lukic_Login_Page_Designer();
