<?php
/**
 * Snippet Name: Disable All Updates
 * Description: Completely disables WordPress core, plugin, theme, and translation updates. Removes update notifications and related functionality.
 * Version: 1.0.0
 * Author: Lukic
 * Author URI: https://Lukic.com
 * Tags: updates, security, maintenance
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:ignoreFile

/**
 * Disable all WordPress updates
 */
function Lukic_disable_all_updates() {
	// Disable core updates
	add_filter( 'auto_update_core', '__return_false' );
	add_filter( 'wp_auto_update_core', '__return_false' );
	add_filter( 'auto_core_update_send_email', '__return_false' );
	add_filter( 'allow_minor_auto_core_updates', '__return_false' );
	add_filter( 'allow_major_auto_core_updates', '__return_false' );
	add_filter( 'auto_update_translation', '__return_false' );
	add_filter( 'automatic_updater_disabled', '__return_true' );

	// Disable plugin and theme updates
	add_filter( 'auto_update_plugin', '__return_false' );
	add_filter( 'auto_update_theme', '__return_false' );
	add_filter( 'plugins_auto_update_enabled', '__return_false' );
	add_filter( 'themes_auto_update_enabled', '__return_false' );

	// Disable update checks
	remove_action( 'admin_init', '_maybe_update_core' );
	remove_action( 'wp_version_check', 'wp_version_check' );
	remove_action( 'load-update-core.php', 'wp_update_plugins' );
	remove_action( 'load-update-core.php', 'wp_update_themes' );

	// Disable translation updates
	add_filter( 'auto_update_translation', '__return_false' );

	// Remove update page
	add_action( 'admin_menu', 'Lukic_remove_update_menu' );

	// Disable update emails
	add_filter( 'auto_core_update_send_email', '__return_false' );
	add_filter( 'send_core_update_notification_email', '__return_false' );
	add_filter( 'automatic_updates_send_debug_email', '__return_false' );

	// Disable update cron events
	add_action( 'admin_init', 'Lukic_disable_update_crons' );

	// Remove dashboard update notifications
	add_action( 'admin_init', 'Lukic_remove_update_nags' );

	// Disable Site Health update checks
	add_filter( 'site_status_tests', 'Lukic_disable_health_checks' );
}
add_action( 'init', 'Lukic_disable_all_updates' );

/**
 * Remove Updates menu item
 */
function Lukic_remove_update_menu() {
	remove_submenu_page( 'index.php', 'update-core.php' );
}

/**
 * Disable WordPress update cron events
 */
function Lukic_disable_update_crons() {
	remove_action( 'wp_version_check', 'wp_version_check' );
	remove_action( 'wp_update_plugins', 'wp_update_plugins' );
	remove_action( 'wp_update_themes', 'wp_update_themes' );
	remove_action( 'wp_maybe_auto_update', 'wp_maybe_auto_update' );

	wp_clear_scheduled_hook( 'wp_version_check' );
	wp_clear_scheduled_hook( 'wp_update_plugins' );
	wp_clear_scheduled_hook( 'wp_update_themes' );
	wp_clear_scheduled_hook( 'wp_maybe_auto_update' );
}

/**
 * Remove update notifications
 */
function Lukic_remove_update_nags() {
	remove_action( 'admin_notices', 'update_nag', 3 );
	remove_action( 'admin_notices', 'maintenance_nag', 10 );
}

/**
 * Disable Site Health update checks
 */
function Lukic_disable_health_checks( $tests ) {
	unset( $tests['async']['background_updates'] );
	unset( $tests['direct']['wordpress_version'] );
	unset( $tests['direct']['plugin_version'] );
	unset( $tests['direct']['theme_version'] );
	return $tests;
}

// Hide WordPress version number
add_filter( 'update_footer', '__return_empty_string', 11 );
add_filter( 'core_version_check_api_url', '__return_false' );

// Prevent WP from checking for updates
add_filter( 'pre_site_transient_update_' . 'core', '__return_null' );
add_filter( 'pre_site_transient_update_' . 'plugins', '__return_null' );
add_filter( 'pre_site_transient_update_' . 'themes', '__return_null' );
