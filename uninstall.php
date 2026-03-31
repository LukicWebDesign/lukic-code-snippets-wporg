<?php
/**
 * Uninstall Lukic Code Snippets
 *
 * This file runs when the plugin is deleted from the WordPress admin.
 *
 * @package Lukic_Snippet_Codes
 */

// Exit if not called by WordPress.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Wrap in a function to avoid naked execution logic
function lukic_code_snippets_uninstall() {
	// Get the cleanup setting.
	$cleanup_data = get_option( 'Lukic_snippet_codes_cleanup', 'preserve' );

	// If user has chosen to delete all data.
	if ( 'delete' === $cleanup_data ) {
		global $wpdb;
		if ( ! defined( 'Lukic_SNIPPET_CODES_PLUGIN_DIR' ) ) {
			define( 'Lukic_SNIPPET_CODES_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
		}

		require_once Lukic_SNIPPET_CODES_PLUGIN_DIR . 'includes/snippets/class-snippet-registry.php';
		// Delete plugin options.
		delete_option( 'Lukic_snippet_codes_options' );
		delete_option( 'Lukic_snippet_codes_cleanup' );
		$cleanup_items = Lukic_Snippet_Registry::get_cleanup_items();
		if ( ! empty( $cleanup_items['options'] ) ) {
			foreach ( $cleanup_items['options'] as $option_name ) {
				delete_option( $option_name );
			}
		}

		if ( ! empty( $cleanup_items['transients'] ) ) {
			foreach ( $cleanup_items['transients'] as $transient_name ) {
				delete_transient( $transient_name );
			}
		}

		if ( ! empty( $cleanup_items['tables'] ) ) {
			foreach ( $cleanup_items['tables'] as $table ) {
				$table_name = ( strpos( $table, $wpdb->prefix ) === 0 ) ? $table : $wpdb->prefix . $table;
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, PluginCheck.Security.DirectDB.UnescapedDBParameter
				$wpdb->query( "DROP TABLE IF EXISTS $table_name" );
			}
		}

		// Clear any scheduled events.
		wp_clear_scheduled_hook( 'Lukic_snippet_codes_daily_cleanup' );
	}
}

// Execute uninstall logic
lukic_code_snippets_uninstall();
