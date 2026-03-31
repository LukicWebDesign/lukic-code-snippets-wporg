<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Snippet: Admin Bar Site Visibility Indicator
 * Description: Shows whether search engines are discouraged from indexing the site
 */

if ( ! function_exists( 'Lukic_add_site_visibility_indicator' ) ) {
	/**
	 * Add site visibility indicator to the admin bar
	 */
	function Lukic_add_site_visibility_indicator() {
		global $wp_admin_bar;

		// Only show for users who can manage options
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Check if search engines are discouraged from indexing the site
		$blog_public = get_option( 'blog_public' );

		// Set text and class based on visibility status
		if ( $blog_public == 0 ) {
			$visibility_text = 'Site Visibility: OFF';
		} else {
			$visibility_text = 'Site Visibility: ON';
		}

		// Add the node to the admin bar
		$wp_admin_bar->add_node(
			array(
				'id'     => 'site-visibility-status',
				'parent' => 'top-secondary',
				'title'  => '<span class="ab-label">' . $visibility_text . '</span>',
				'href'   => admin_url( 'options-reading.php' ),
				'meta'   => array(
					'title' => 'Click to edit site visibility settings',
				),
			)
		);
	}
	add_action( 'wp_before_admin_bar_render', 'Lukic_add_site_visibility_indicator' );
}

if ( ! function_exists( 'Lukic_site_visibility_indicator_css' ) ) {
	/**
	 * Enqueue CSS for the visibility indicator.
	 */
	function Lukic_site_visibility_indicator_css() {
		if ( ! current_user_can( 'manage_options' ) || ! is_admin_bar_showing() ) {
			return;
		}

		$blog_public      = get_option( 'blog_public' );
		$background_color = ( $blog_public == 0 ) ? '#dc3232' : '#46b450';
		$handle           = 'Lukic-site-visibility';

		wp_enqueue_style(
			$handle,
			plugin_dir_url( __DIR__ ) . 'assets/css/site-visibility.css',
			array(),
			Lukic_SNIPPET_CODES_VERSION
		);
		wp_add_inline_style(
			$handle,
			':root { --lukic-site-visibility-indicator-bg: ' . esc_attr( $background_color ) . '; }'
		);
	}
	add_action( 'admin_enqueue_scripts', 'Lukic_site_visibility_indicator_css' );
	add_action( 'wp_enqueue_scripts', 'Lukic_site_visibility_indicator_css' );
}
