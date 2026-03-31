<?php
/**
 * Show Active Plugins First
 *
 * Show active plugins at the top of plugins list separated from inactive plugins for easier management.
 *
 * @package Lukic_Code_Snippets
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Reorder plugins list to show active plugins first
 *
 * @param array $plugins An array of plugin data.
 * @return array Filtered array of plugin data.
 */
function lukic_show_active_plugins_first( $plugins ) {
	global $pagenow;

	// Only run on plugins page
	if ( 'plugins.php' !== $pagenow ) {
		return $plugins;
	}

	// Get active plugins
	$active_plugins = get_option( 'active_plugins' );
	
	// If no active plugins, return original list
	if ( empty( $active_plugins ) ) {
		return $plugins;
	}

	foreach ( $plugins as $plugin_path => $plugin_data ) {
		if ( in_array( $plugin_path, $active_plugins, true ) || is_plugin_active_for_network( $plugin_path ) ) {
			// Active plugin - do nothing (keep original name)
		} else {
			// Inactive plugin - Prepend a zero-width space to the name
			// Based on previous test, "Modified" > "Unmodified" in sort order
			// So this pushes Inactive to the bottom
			$plugins[ $plugin_path ]['Name'] = "\xE2\x80\x8B" . $plugin_data['Name'];
		}
	}

	return $plugins;
}
add_filter( 'all_plugins', 'lukic_show_active_plugins_first' );

/**
 * Add visual separator between active and inactive plugins
 */
function lukic_active_plugins_separator_css() {
	global $pagenow;
	if ( 'plugins.php' !== $pagenow ) {
		return;
	}
	wp_add_inline_style( 'wp-admin', '
		tr.active + tr.inactive th,
		tr.active + tr.inactive td {
			border-top: 2px solid #2271b1 !important;
			position: relative;
		}
	' );
}
add_action( 'admin_enqueue_scripts', 'lukic_active_plugins_separator_css' );
