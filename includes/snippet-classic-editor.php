<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Snippet: Enable Classic Editor
 * Description: Disables Gutenberg block editor and enables the classic editor for all post types
 */

if ( ! function_exists( 'Lukic_enable_classic_editor' ) ) {
	/**
	 * Main function to enable classic editor
	 */
	function Lukic_enable_classic_editor() {
		// Disable Gutenberg editor
		add_filter( 'use_block_editor_for_post', '__return_false', 100 );
		add_filter( 'use_block_editor_for_post_type', '__return_false', 100 );

		// Disable Gutenberg widgets
		add_filter( 'gutenberg_use_widgets_block_editor', '__return_false' );
		add_filter( 'use_widgets_block_editor', '__return_false' );

		// Remove "Try Gutenberg" dashboard widget
		remove_action( 'try_gutenberg_panel', 'wp_try_gutenberg_panel' );

		// Disable loading of block editor assets
		add_action( 'wp_enqueue_scripts', 'Lukic_disable_block_editor_assets', 100 );

		// Remove Gutenberg CSS (only if needed)
		add_action( 'wp_enqueue_scripts', 'Lukic_disable_gutenberg_css', 100 );

		// Force TinyMCE as the default editor
		add_filter( 'wp_default_editor', 'Lukic_default_editor' );
	}
	Lukic_enable_classic_editor();

	/**
	 * Disable block editor assets
	 */
	function Lukic_disable_block_editor_assets() {
		wp_dequeue_style( 'wp-block-library' );
		wp_dequeue_style( 'wp-block-library-theme' );
	}

	/**
	 * Disable Gutenberg CSS
	 */
	function Lukic_disable_gutenberg_css() {
		wp_dequeue_style( 'wp-block-library' );
		wp_dequeue_style( 'wp-block-library-theme' );
		wp_dequeue_style( 'wc-block-style' ); // WooCommerce block CSS
	}

	/**
	 * Set TinyMCE as default editor
	 */
	function Lukic_default_editor() {
		return 'tinymce';
	}

	/**
	 * Add admin notice about classic editor being enabled
	 */
	function Lukic_classic_editor_notice() {
		$screen = get_current_screen();
		if ( $screen->base == 'post' || $screen->base == 'page' ) {
			echo '<div class="notice notice-info is-dismissible">';
			echo '<p>The Classic Editor is enabled by <strong>Lukic Snippet Codes</strong>. You can disable this in Settings &gt; Lukic Snippets.</p>';
			echo '</div>';
		}
	}
	add_action( 'admin_notices', 'Lukic_classic_editor_notice' );
}
