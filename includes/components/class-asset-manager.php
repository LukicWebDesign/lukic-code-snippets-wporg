<?php
/**
 * Asset Manager Class for Lukic Code Snippets
 *
 * Handles registration and enqueuing of CSS and JS files
 *
 * @package Lukic_Snippet_Codes
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Lukic_Asset_Manager {

	/**
	 * Plugin directory URL
	 *
	 * @var string
	 */
	private $plugin_url;

	/**
	 * Plugin directory path
	 *
	 * @var string
	 */
	private $plugin_path;

	/**
	 * Plugin version
	 *
	 * @var string
	 */
	private $version;

	/**
	 * Constructor
	 */
	public function __construct() {

		$this->plugin_url  = defined( 'Lukic_SNIPPET_CODES_PLUGIN_URL' ) ? Lukic_SNIPPET_CODES_PLUGIN_URL : plugin_dir_url( dirname( __DIR__ ) );
		$this->plugin_path = defined( 'Lukic_SNIPPET_CODES_PLUGIN_DIR' ) ? Lukic_SNIPPET_CODES_PLUGIN_DIR : plugin_dir_path( dirname( __DIR__ ) );
		$this->version     = Lukic_SNIPPET_CODES_VERSION;

		add_action( 'admin_init', array( $this, 'register_assets' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Register all CSS and JS assets
	 */
	public function register_assets() {
		$this->register_styles();
		$this->register_scripts();
	}

	/**
	 * Register CSS stylesheets
	 */
	private function register_styles() {
		$styles = array(
			// Core Framework Styles (must be loaded in order)
			'Lukic-variables'            => array(
				'src'         => 'assets/css/variables.css',
				'deps'        => array(),
				'description' => 'CSS Variables Framework',
			),
			'Lukic-framework'            => array(
				'src'         => 'assets/css/framework.css',
				'deps'        => array( 'Lukic-variables' ),
				'description' => 'Main CSS Framework',
			),
			'Lukic-admin-styles'         => array(
				'src'         => 'assets/css/admin-style.css',
				'deps'        => array( 'Lukic-framework' ),
				'description' => 'Legacy Admin Styles',
			),
			'Lukic-datatables-css'       => array(
				'src'         => 'assets/css/jquery.dataTables.min.css',
				'deps'        => array(),
				'description' => 'DataTables core styles',
			),
			// Feature-specific styles
				'Lukic-image-attributes' => array(
					'src'         => 'assets/css/image-attributes-editor.css',
					'deps'        => array( 'Lukic-framework', 'Lukic-datatables-css', 'thickbox' ),
					'description' => 'Image Attributes Editor',
				),
			'Lukic-maintenance-mode'     => array(
				'src'         => 'assets/css/maintenance-mode-admin.css',
				'deps'        => array( 'Lukic-framework' ),
				'description' => 'Maintenance Mode Admin',
			),
			'Lukic-meta-tags'            => array(
				'src'         => 'assets/css/meta-tags-editor.css',
				'deps'        => array( 'Lukic-framework', 'Lukic-datatables-css' ),
				'description' => 'Meta Tags Editor',
			),
		);

		foreach ( $styles as $handle => $style ) {
			$file_path = $this->plugin_path . $style['src'];

			if ( file_exists( $file_path ) ) {
				wp_register_style( $handle, $this->plugin_url . $style['src'], $style['deps'], $this->get_asset_version( $file_path ) );
			}
		}
	}

	/**
	 * Register JavaScript files
	 */
	private function register_scripts() {
		$scripts = array(
			'Lukic-acf-columns'      => array(
				'src'         => 'assets/js/acf-columns.js',
				'deps'        => array( 'jquery' ),
				'in_footer'   => true,
				'description' => 'ACF Columns functionality',
			),
			'Lukic-content-order'    => array(
				'src'         => 'assets/js/content-order.js',
				'deps'        => array( 'jquery', 'jquery-ui-sortable' ),
				'in_footer'   => true,
				'description' => 'Content Order functionality',
			),
			'Lukic-db-tables'        => array(
				'src'         => 'assets/js/db-tables-manager.js',
				'deps'        => array( 'jquery', 'jquery-ui-dialog', 'Lukic-datatables' ),
				'in_footer'   => true,
				'description' => 'Database Tables Manager',
			),
			'Lukic-fluid-typography' => array(
				'src'         => 'assets/js/fluid-typography.js',
				'deps'        => array( 'jquery' ),
				'in_footer'   => true,
				'description' => 'Fluid Typography Calculator',
			),
			'Lukic-image-attributes' => array(
				'src'         => 'assets/js/image-attributes-editor.js',
				'deps'        => array( 'jquery', 'Lukic-datatables', 'thickbox' ),
				'in_footer'   => true,
				'description' => 'Image Attributes Editor',
			),
			'Lukic-maintenance-mode' => array(
				'src'         => 'assets/js/maintenance-mode-admin.js',
				'deps'        => array( 'jquery' ),
				'in_footer'   => true,
				'description' => 'Maintenance Mode Admin',
			),
			'Lukic-meta-tags'        => array(
				'src'         => 'assets/js/meta-tags-editor.js',
				'deps'        => array( 'jquery', 'Lukic-datatables' ),
				'in_footer'   => true,
				'description' => 'Meta Tags Editor',
			),
			'Lukic-redirect-manager' => array(
				'src'         => 'assets/js/redirect-manager.js',
				'deps'        => array( 'jquery', 'jquery-ui-dialog' ),
				'in_footer'   => true,
				'description' => 'Redirect Manager',
			),
			'Lukic-datatables'       => array(
				'src'         => 'assets/js/jquery.dataTables.min.js',
				'deps'        => array( 'jquery' ),
				'in_footer'   => true,
				'description' => 'DataTables library',
			),
			'Lukic-auto-save'        => array(
				'src'         => 'assets/js/auto-save.js',
				'deps'        => array( 'jquery' ),
				'in_footer'   => true,
				'description' => 'Auto-save functionality for snippet toggles',
			),
		);

		foreach ( $scripts as $handle => $script ) {
			$file_path = $this->plugin_path . $script['src'];

			if ( file_exists( $file_path ) ) {
				wp_register_script( $handle, $this->plugin_url . $script['src'], $script['deps'], $this->get_asset_version( $file_path ), $script['in_footer'] );
			}
		}
	}

	/**
	 * Enqueue assets based on current page
	 *
	 * @param string $hook Current page hook
	 */
	public function enqueue_assets( $hook ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$current_page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';

		// Always enqueue core framework on plugin pages
		if ( $this->is_plugin_page( $current_page ) ) {
			$this->enqueue_core_styles();
			$this->enqueue_page_specific_assets( $current_page );
		}

		// Debug: Log which page and assets are being loaded
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( "Lukic Asset Manager - Page: $current_page, Hook: $hook" );
			if ( $this->is_plugin_page( $current_page ) ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( 'Lukic Asset Manager - Enqueuing core styles for plugin page' );
			}
		}
	}

	/**
	 * Check if current page is a plugin page
	 *
	 * @param string $page Current page slug
	 * @return bool
	 */
	private function is_plugin_page( $page ) {
		return strpos( $page, 'lukic-' ) === 0;
	}

	/**
	 * Enqueue core CSS framework
	 */
	private function enqueue_core_styles() {
		wp_enqueue_style( 'Lukic-variables' );
		wp_enqueue_style( 'Lukic-framework' );
		wp_enqueue_style( 'Lukic-admin-styles' );
	}

	/**
	 * Enqueue page-specific assets
	 *
	 * @param string $current_page Current page slug
	 */
	private function enqueue_page_specific_assets( $current_page ) {
		$page_assets = array(
			'lukic-code-snippets'           => array(
				'styles'  => array(),
				'scripts' => array( 'Lukic-auto-save' ),
			),
			'lukic-acf-columns'             => array(
				'scripts' => array( 'Lukic-acf-columns' ),
			),
			'lukic-content-order'           => array(
				'scripts' => array( 'Lukic-content-order' ),
			),
			'lukic-order-book'              => array(
				'scripts' => array( 'Lukic-content-order' ),
			),
			'lukic-fluid-typography'        => array(
				'scripts' => array( 'Lukic-fluid-typography' ),
			),
			'lukic-db-tables-manager'       => array(
				'styles'  => array( 'Lukic-datatables-css' ),
				'scripts' => array( 'Lukic-db-tables' ),
			),
			'lukic-image-attributes-editor' => array(
				'styles'  => array( 'Lukic-image-attributes' ),
				'scripts' => array( 'Lukic-image-attributes' ),
			),
			'lukic-maintenance-mode'        => array(
				'styles'  => array( 'Lukic-maintenance-mode' ),
				'scripts' => array( 'Lukic-maintenance-mode' ),
			),
			'lukic-meta-tags-editor'        => array(
				'styles'  => array( 'Lukic-meta-tags' ),
				'scripts' => array( 'Lukic-meta-tags' ),
			),
			'lukic-redirect-manager'        => array(
				'styles'  => array(),
				'scripts' => array( 'Lukic-redirect-manager' ),
			),
		);

		if ( isset( $page_assets[ $current_page ] ) ) {
			$assets = $page_assets[ $current_page ];

			// Enqueue styles
			if ( isset( $assets['styles'] ) ) {
				foreach ( $assets['styles'] as $style ) {
					wp_enqueue_style( $style );
				}
			}

			// Enqueue scripts
			if ( isset( $assets['scripts'] ) ) {
				foreach ( $assets['scripts'] as $script ) {
					wp_enqueue_script( $script );

					// Localize auto-save script
					if ( $script === 'Lukic-auto-save' ) {
						wp_localize_script(
							$script,
							'Lukic_auto_save',
							array(
								'nonce'        => wp_create_nonce( 'Lukic_auto_save_nonce' ),
								'activated'    => __( 'Activated', 'lukic-code-snippets' ),
								'deactivated'  => __( 'Deactivated', 'lukic-code-snippets' ),
								'error_saving' => __( 'Error saving settings', 'lukic-code-snippets' ),
							)
						);
					}
				}
			}
		}
	}

	/**
	 * Get registered assets for debugging
	 *
	 * @return array
	 */
	public function get_registered_assets() {
		global $wp_styles, $wp_scripts;

		return array(
			'styles'  => array_keys( $wp_styles->registered ),
			'scripts' => array_keys( $wp_scripts->registered ),
		);
	}

	/**
	 * Determine version for an asset based on file modification time.
	 *
	 * @param string $file_path
	 * @return string
	 */
	private function get_asset_version( $file_path ) {

		$mtime = file_exists( $file_path ) ? filemtime( $file_path ) : false;
		if ( $mtime ) {
			return $this->version . '.' . $mtime;
		}

		return $this->version;
	}
}
