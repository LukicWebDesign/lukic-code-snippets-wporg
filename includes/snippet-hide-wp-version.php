<?php
/**
 * Hide WordPress Version
 *
 * Enhance security by hiding the WordPress version number from your site's source view,
 * thwarting targeted attacks.
 *
 * @package Lukic_Snippet_Codes
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Remove WordPress version from head
 */
function Lukic_remove_wp_version_from_head() {
	remove_action( 'wp_head', 'wp_generator' );
}
add_action( 'init', 'Lukic_remove_wp_version_from_head' );

/**
 * Remove WordPress version from RSS feeds
 */
function Lukic_remove_version_from_rss() {
	return '';
}
add_filter( 'the_generator', 'Lukic_remove_version_from_rss' );

/**
 * Remove WordPress version from scripts and styles
 */
function Lukic_remove_wp_version_from_scripts( $src ) {
	if ( strpos( $src, 'ver=' ) ) {
		$src = remove_query_arg( 'ver', $src );
	}
	return $src;
}
add_filter( 'style_loader_src', 'Lukic_remove_wp_version_from_scripts', 9999 );
add_filter( 'script_loader_src', 'Lukic_remove_wp_version_from_scripts', 9999 );
