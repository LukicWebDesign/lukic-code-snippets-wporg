<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Snippet: Wider Admin Menu
 * Description: Makes the WordPress admin menu wider for better readability
 */

if ( ! function_exists( 'Lukic_wider_admin_menu' ) ) {
	/**
	 * Makes the WordPress admin menu wider
	 */
	function Lukic_wider_admin_menu() {
		// Set your desired menu width in pixels (default WordPress is 160px)
		$menu_width      = 200;
		$collapsed_width = 40; // Width of collapsed menu

		// Calculate content margins based on menu states
		$content_margin           = $menu_width + 20;
		$collapsed_content_margin = $collapsed_width + 20;

		wp_enqueue_style(
			'Lukic-wider-admin-menu',
			plugin_dir_url( __DIR__ ) . 'assets/css/wider-admin-menu.css',
			array(),
			Lukic_SNIPPET_CODES_VERSION
		);
		wp_add_inline_style(
			'Lukic-wider-admin-menu',
			':root {' .
				'--lukic-admin-menu-width: ' . absint( $menu_width ) . 'px;' .
				'--lukic-admin-menu-collapsed-width: ' . absint( $collapsed_width ) . 'px;' .
				'--lukic-admin-menu-content-margin: ' . absint( $content_margin ) . 'px;' .
				'--lukic-admin-menu-collapsed-margin: ' . absint( $collapsed_content_margin ) . 'px;' .
			'}'
		);
	}
	// Use a high priority (999) to ensure this runs after WordPress core styles
	add_action( 'admin_enqueue_scripts', 'Lukic_wider_admin_menu', 999 );
}

// Add a debug function to verify the snippet is loaded
if ( ! function_exists( 'Lukic_wider_admin_menu_debug' ) ) {
	function Lukic_wider_admin_menu_debug() {
		// Only show for admins
		if ( current_user_can( 'manage_options' ) ) {
			echo '<!-- Lukic Wider Admin Menu snippet is active -->';
		}
	}
	add_action( 'admin_footer', 'Lukic_wider_admin_menu_debug' );
}
