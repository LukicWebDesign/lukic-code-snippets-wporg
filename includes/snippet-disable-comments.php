<?php
/**
 * Disable Comments
 *
 * Remove comment functionality across your WordPress site, helping reduce spam,
 * moderation workload, and database clutter.
 *
 * @package Lukic_Code_Snippets
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Disable support for comments and trackbacks in post types
 */
function lukic_disable_comments_post_types_support() {
	$post_types = get_post_types();
	foreach ( $post_types as $post_type ) {
		// Skip WooCommerce products
		if ( 'product' === $post_type ) {
			continue;
		}
		
		if ( post_type_supports( $post_type, 'comments' ) ) {
			remove_post_type_support( $post_type, 'comments' );
			remove_post_type_support( $post_type, 'trackbacks' );
		}
	}
}
add_action( 'init', 'lukic_disable_comments_post_types_support' );

/**
 * Close comments on the front-end
 *
 * @param bool $open    Whether the current post is open for comments.
 * @param int  $post_id The post ID.
 * @return bool
 */
function lukic_disable_comments_status( $open, $post_id ) {
	$post = get_post( $post_id );
	if ( $post && 'product' === $post->post_type ) {
		return $open;
	}
	return false;
}
add_filter( 'comments_open', 'lukic_disable_comments_status', 20, 2 );
add_filter( 'pings_open', 'lukic_disable_comments_status', 20, 2 );

/**
 * Hide existing comments
 *
 * @param array $comments Array of comments.
 * @param int   $post_id  Post ID.
 * @return array
 */
function lukic_disable_comments_hide_existing( $comments, $post_id ) {
	$post = get_post( $post_id );
	if ( $post && 'product' === $post->post_type ) {
		return $comments;
	}
	return array();
}
add_filter( 'comments_array', 'lukic_disable_comments_hide_existing', 10, 2 );

/**
 * Remove comments page in menu
 */
function lukic_disable_comments_admin_menu() {
	remove_menu_page( 'edit-comments.php' );
}
add_action( 'admin_menu', 'lukic_disable_comments_admin_menu' );

/**
 * Redirect any user trying to access comments page
 */
function lukic_disable_comments_admin_menu_redirect() {
	global $pagenow;
	if ( 'edit-comments.php' === $pagenow ) {
		wp_safe_redirect( admin_url() );
		exit;
	}
}
add_action( 'admin_init', 'lukic_disable_comments_admin_menu_redirect' );

/**
 * Remove comments metabox from dashboard
 */
function lukic_disable_comments_dashboard() {
	remove_meta_box( 'dashboard_recent_comments', 'dashboard', 'normal' );
}
add_action( 'wp_dashboard_setup', 'lukic_disable_comments_dashboard' );

/**
 * Remove comments links from admin bar
 */
function lukic_disable_comments_admin_bar() {
	global $wp_admin_bar;
	$wp_admin_bar->remove_menu( 'comments' );
}
add_action( 'wp_before_admin_bar_render', 'lukic_disable_comments_admin_bar' );
