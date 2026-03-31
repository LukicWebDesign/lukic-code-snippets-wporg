<?php
/**
 * Disable File Editing
 *
 * Disable the theme and plugin file editor in WordPress admin to prevent unauthorized code changes
 * and reduce security risks from compromised accounts.
 *
 * @package Lukic_Snippet_Codes
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Disable file editing in WordPress
 */
function Lukic_disable_file_editing() {
	// Define the constant if not already defined
	if ( ! defined( 'DISALLOW_FILE_EDIT' ) ) {
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound
		define( 'DISALLOW_FILE_EDIT', true );
	}

	// Fallback: Remove menu items if constant doesn't work (e.g. defined elsewhere as false)
	if ( ! defined( 'DISALLOW_FILE_EDIT' ) || ! DISALLOW_FILE_EDIT ) {
		add_action( 'admin_menu', 'Lukic_remove_editor_menu_items', 999 );
	}
}
add_action( 'init', 'Lukic_disable_file_editing' );

/**
 * Remove editor menu items
 */
function Lukic_remove_editor_menu_items() {
	remove_submenu_page( 'themes.php', 'theme-editor.php' );
	remove_submenu_page( 'plugins.php', 'plugin-editor.php' );
}

/**
 * Block direct access to editor pages
 */
function Lukic_block_editor_access() {
	// Check if we are on an editor page
	$pagenow = isset( $GLOBALS['pagenow'] ) ? $GLOBALS['pagenow'] : '';
	
	if ( 'theme-editor.php' === $pagenow || 'plugin-editor.php' === $pagenow ) {
		// Allow if DISALLOW_FILE_EDIT is explicitly set to false in wp-config (user override)
		// But if our snippet is active, we probably want to enforce it. 
		// However, if the constant is false, it means the user might want it enabled despite our snippet?
		// The requirement is "Disable the theme and plugin file editor".
		// So we should enforce it.
		
		wp_die( esc_html__( 'File editing is disabled on this site.', 'lukic-code-snippets' ), 403 );
	}
}
add_action( 'admin_init', 'Lukic_block_editor_access' );
