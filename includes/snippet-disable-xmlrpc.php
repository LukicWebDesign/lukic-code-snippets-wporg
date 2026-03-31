<?php
/**
 * Disable XML-RPC
 *
 * Increase security by disabling XML-RPC to prevent external applications from
 * interfacing with your WordPress site, reducing vulnerability to attacks.
 *
 * @package Lukic_Snippet_Codes
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Disable XML-RPC functionality
 */
function Lukic_disable_xmlrpc() {
	// Disable XML-RPC methods that require authentication
	add_filter( 'xmlrpc_enabled', '__return_false' );

	// Remove XML-RPC headers
	remove_action( 'wp_head', 'rsd_link' );
	remove_action( 'wp_head', 'wlwmanifest_link' );

	// Return a 403 Forbidden status for XML-RPC requests
	add_filter(
		'xmlrpc_methods',
		function ( $methods ) {
			return array();
		},
		999
	);
}
add_action( 'init', 'Lukic_disable_xmlrpc' );

/**
 * Block access to xmlrpc.php
 */
function Lukic_block_xmlrpc_request() {
	// Check if the current request is for xmlrpc.php
	$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
	if ( strpos( $request_uri, 'xmlrpc.php' ) !== false ) {
		// Return 403 Forbidden status
		status_header( 403 );
		// Exit with error message
		wp_die( 'XML-RPC services are disabled on this site.', 'XML-RPC Disabled', array( 'response' => 403 ) );
	}
}
add_action( 'init', 'Lukic_block_xmlrpc_request', 1 );
