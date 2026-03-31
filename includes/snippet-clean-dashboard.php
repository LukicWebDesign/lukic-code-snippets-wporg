<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Snippet: Clean Dashboard
 * Description: Removes default WordPress dashboard widgets for a clean admin experience
 */

if ( ! function_exists( 'Lukic_clean_dashboard_init' ) ) {
	/**
	 * Initialize the dashboard cleaning functionality
	 */
	function Lukic_clean_dashboard_init() {
		// Multiple approaches to ensure widgets are removed
		add_action( 'wp_dashboard_setup', 'Lukic_remove_dashboard_widgets', 9999 );
		add_action( 'admin_menu', 'Lukic_remove_dashboard_widgets', 9999 );

		// More aggressive approach specifically for Events and News widget
		add_action( 'admin_init', 'Lukic_remove_news_widget', 9999 );

		// Remove welcome panel
		add_action( 'admin_init', 'Lukic_remove_welcome_panel' );

		// Remove plugin dashboard widgets
		add_action( 'wp_dashboard_setup', 'Lukic_remove_plugin_dashboard_widgets', 9999 );
	}
	Lukic_clean_dashboard_init();

	/**
	 * Remove default WordPress dashboard widgets
	 */
	function Lukic_remove_dashboard_widgets() {
		global $wp_meta_boxes;

		// First approach: Use remove_meta_box function
		remove_meta_box( 'dashboard_primary', 'dashboard', 'normal' );           // WordPress Events and News
		remove_meta_box( 'dashboard_primary', 'dashboard', 'side' );             // Try side position too
		remove_meta_box( 'dashboard_quick_press', 'dashboard', 'side' );         // Quick Draft
		remove_meta_box( 'dashboard_right_now', 'dashboard', 'normal' );         // At a Glance
		remove_meta_box( 'dashboard_activity', 'dashboard', 'normal' );          // Activity
		remove_meta_box( 'dashboard_site_health', 'dashboard', 'normal' );       // Site Health
		remove_meta_box( 'dashboard_incoming_links', 'dashboard', 'normal' );    // Incoming Links
		remove_meta_box( 'dashboard_plugins', 'dashboard', 'normal' );           // Plugins
		remove_meta_box( 'dashboard_secondary', 'dashboard', 'normal' );         // Other WordPress News
		remove_meta_box( 'dashboard_recent_drafts', 'dashboard', 'side' );       // Recent Drafts
		remove_meta_box( 'dashboard_recent_comments', 'dashboard', 'normal' );   // Recent Comments

		// Second approach: Directly unset from global $wp_meta_boxes
		if ( isset( $wp_meta_boxes['dashboard'] ) ) {
			// Remove all dashboard widgets from all positions
			$positions  = array( 'normal', 'side', 'column3', 'column4' );
			$priorities = array( 'high', 'core', 'default', 'low' );

			foreach ( $positions as $position ) {
				foreach ( $priorities as $priority ) {
					if ( isset( $wp_meta_boxes['dashboard'][ $position ][ $priority ] ) ) {
						// Specifically target WordPress Events and News
						if ( isset( $wp_meta_boxes['dashboard'][ $position ][ $priority ]['dashboard_primary'] ) ) {
							unset( $wp_meta_boxes['dashboard'][ $position ][ $priority ]['dashboard_primary'] );
						}

						// Other widgets
						if ( isset( $wp_meta_boxes['dashboard'][ $position ][ $priority ]['dashboard_quick_press'] ) ) {
							unset( $wp_meta_boxes['dashboard'][ $position ][ $priority ]['dashboard_quick_press'] );
						}
						// And so on for other widgets...
					}
				}
			}
		}
	}

	/**
	 * Specific approach for WordPress Events and News widget
	 */
	function Lukic_remove_news_widget() {
		// Remove the action that adds the news widget
		remove_action( 'wp_network_dashboard_setup', 'wp_dashboard_primary_network' );
		remove_action( 'wp_user_dashboard_setup', 'wp_dashboard_primary_user' );
		remove_action( 'wp_dashboard_setup', 'wp_dashboard_primary' );

		// Also try to remove it from all possible positions
		remove_meta_box( 'dashboard_primary', 'dashboard', 'normal' );
		remove_meta_box( 'dashboard_primary', 'dashboard', 'side' );
		remove_meta_box( 'dashboard_primary', 'dashboard', 'column3' );
		remove_meta_box( 'dashboard_primary', 'dashboard', 'column4' );
	}

	/**
	 * Remove WordPress Welcome Panel
	 */
	function Lukic_remove_welcome_panel() {
		remove_action( 'welcome_panel', 'wp_welcome_panel' );

		// Also set user meta to ensure it stays hidden
		$user_id = get_current_user_id();
		if ( $user_id ) {
			update_user_meta( $user_id, 'show_welcome_panel', 0 );
		}
	}

	/**
	 * Remove dashboard widgets added by common plugins
	 */
	function Lukic_remove_plugin_dashboard_widgets() {
		// Same as before...
		remove_meta_box( 'woocommerce_dashboard_status', 'dashboard', 'normal' );
		remove_meta_box( 'wc_admin_dashboard_setup', 'dashboard', 'normal' );
		remove_meta_box( 'yoast_db_widget', 'dashboard', 'normal' );
		remove_meta_box( 'wpseo-dashboard-overview', 'dashboard', 'normal' );
		remove_meta_box( 'jetpack_summary_widget', 'dashboard', 'normal' );
		remove_meta_box( 'akismet_widget', 'dashboard', 'normal' );
		remove_meta_box( 'monsterinsights_reports_widget', 'dashboard', 'normal' );
		remove_meta_box( 'wordfence_activity_report_widget', 'dashboard', 'normal' );
		remove_meta_box( 'updraft_central_dashboard', 'dashboard', 'normal' );
		remove_meta_box( 'wp_mail_smtp_reports_widget_lite', 'dashboard', 'normal' );
		remove_meta_box( 'cf7_submission_widget', 'dashboard', 'normal' );
		remove_meta_box( 'wpforms_reports_widget_lite', 'dashboard', 'normal' );
	}
}
