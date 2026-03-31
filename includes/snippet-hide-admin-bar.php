<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Snippet: Hide Admin Bar on Frontend
 * Description: Removes the WordPress admin bar from the frontend of your site
 */

if ( ! function_exists( 'Lukic_hide_admin_bar_init' ) ) {
	/**
	 * Initialize the admin bar hiding functionality
	 */
	function Lukic_hide_admin_bar_init() {
		// Remove admin bar from frontend
		add_filter( 'show_admin_bar', 'Lukic_disable_admin_bar', 999 );

		// Remove the margin added to <html> element when admin bar is showing
		add_action( 'wp_enqueue_scripts', 'Lukic_remove_admin_bar_margin', 999 );

		// Add dashboard notice to remind users the admin bar is hidden
		add_action( 'admin_notices', 'Lukic_admin_bar_notice' );
	}
	Lukic_hide_admin_bar_init();

	/**
	 * Disable the admin bar on the frontend
	 *
	 * @param bool $show Whether to show the admin bar or not
	 * @return bool
	 */
	function Lukic_disable_admin_bar( $show ) {
		// Only hide on the frontend, not in admin area
		if ( ! is_admin() ) {
			return false;
		}

		return $show;
	}

	/**
	 * Remove the margin added to the html element by WordPress
	 */
	function Lukic_remove_admin_bar_margin() {
		if ( ! is_admin() ) {
			wp_add_inline_style( 'wp-block-library', '
				html { margin-top: 0 !important; }
				body.admin-bar { margin-top: 0 !important; padding-top: 0 !important; }
				#wpadminbar { display: none !important; }
			' );
		}
	}

	/**
	 * Display notice in admin area to remind that admin bar is hidden on frontend
	 */
	function Lukic_admin_bar_notice() {
		// Only show on dashboard
		$screen = get_current_screen();
		if ( $screen->id !== 'dashboard' ) {
			return;
		}

		// Only show if current user can manage options
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Get the settings page URL
		$settings_url = admin_url( 'options-general.php?page=Lukic-code-snippets' );

		echo '<div class="notice notice-info is-dismissible">';
		echo '<p>';
		echo wp_kses(
			sprintf(
				/* translators: %s: URL to the plugin settings page */
				__( 'The Admin Bar is currently hidden on the frontend of your site. <a href="%s">Manage this setting</a> in Lukic Snippet Codes.', 'lukic-code-snippets' ),
				esc_url( $settings_url )
			),
			array( 'a' => array( 'href' => array() ) )
		);
		echo '</p>';
		echo '</div>';
	}
}
