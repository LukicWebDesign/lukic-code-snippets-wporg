<?php
/**
 * Helper Utilities Class for Lukic Code Snippets
 *
 * Common utility functions used across the plugin
 *
 * @package Lukic_Snippet_Codes
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Lukic_Helpers {

	/**
	 * Sanitize and validate plugin settings
	 *
	 * @param array $options Raw options array
	 * @return array Sanitized options
	 */
	public static function sanitize_options( $options ) {
		if ( ! is_array( $options ) ) {
			return array();
		}

		$sanitized = array();
		foreach ( $options as $key => $value ) {
			$sanitized[ sanitize_key( $key ) ] = (int) $value;
		}

		return $sanitized;
	}

	/**
	 * Check if current page is a plugin admin page
	 *
	 * @return bool
	 */
	public static function is_plugin_admin_page() {
		if ( ! is_admin() ) {
			return false;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$current_page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
		return strpos( $current_page, 'lukic-' ) === 0;
	}

	/**
	 * Get current plugin page slug
	 *
	 * @return string|false
	 */
	public static function get_current_page_slug() {
		if ( ! self::is_plugin_admin_page() ) {
			return false;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : false;
	}

	/**
	 * Generate admin notice HTML
	 *
	 * @param string $message Notice message
	 * @param string $type    Notice type (success, error, warning, info)
	 * @param bool   $dismissible Whether notice is dismissible
	 * @return string HTML for notice
	 */
	public static function generate_admin_notice( $message, $type = 'info', $dismissible = true ) {
		$classes   = array( 'notice', 'Lukic-notification' );
		$classes[] = 'Lukic-notification--' . $type;

		if ( $dismissible ) {
			$classes[] = 'is-dismissible';
		}

		return sprintf(
			'<div class="%s"><p>%s</p></div>',
			esc_attr( implode( ' ', $classes ) ),
			esc_html( $message )
		);
	}

	/**
	 * Get snippet status badge HTML
	 *
	 * @param bool $is_active Whether snippet is active
	 * @return string Badge HTML
	 */
	public static function get_status_badge( $is_active ) {
		if ( $is_active ) {
			return '<span class="Lukic-badge Lukic-badge--success">Active</span>';
		} else {
			return '<span class="Lukic-badge Lukic-badge--error">Inactive</span>';
		}
	}

	/**
	 * Format file size for display
	 *
	 * @param int $size Size in bytes
	 * @return string Formatted size
	 */
	public static function format_file_size( $size ) {
		$units = array( 'B', 'KB', 'MB', 'GB' );
		$power = floor( log( $size, 1024 ) );
		$power = min( $power, count( $units ) - 1 );

		$size /= pow( 1024, $power );

		return round( $size, 2 ) . ' ' . $units[ $power ];
	}

	/**
	 * Get plugin version
	 *
	 * @return string
	 */
	public static function get_plugin_version() {
		return defined( 'Lukic_SNIPPET_CODES_VERSION' ) ? Lukic_SNIPPET_CODES_VERSION : '1.0.0';
	}

	/**
	 * Get plugin directory URL
	 *
	 * @return string
	 */
	public static function get_plugin_url() {
		return plugin_dir_url( dirname( __DIR__ ) );
	}

	/**
	 * Get plugin directory path
	 *
	 * @return string
	 */
	public static function get_plugin_path() {
		return plugin_dir_path( dirname( __DIR__ ) );
	}

	/**
	 * Log debug message (if WP_DEBUG is enabled)
	 *
	 * @param mixed  $message Message to log
	 * @param string $context Context/component name
	 */
	public static function debug_log( $message, $context = 'Lukic' ) {
		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
			return;
		}

		if ( is_array( $message ) || is_object( $message ) ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
			$message = print_r( $message, true );
		}

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( sprintf( '[%s] %s', $context, $message ) );
	}

	/**
	 * Check if user has required capability
	 *
	 * @param string $capability Capability to check
	 * @return bool
	 */
	public static function user_can( $capability = 'manage_options' ) {
		return current_user_can( $capability );
	}

	/**
	 * Get nonce field for forms
	 *
	 * @param string $action Nonce action
	 * @param string $name   Nonce field name
	 * @return string Nonce field HTML
	 */
	public static function get_nonce_field( $action, $name = '_wpnonce' ) {
		return wp_nonce_field( $action, $name, true, false );
	}

	/**
	 * Verify nonce
	 *
	 * @param string $nonce  Nonce value
	 * @param string $action Nonce action
	 * @return bool
	 */
	public static function verify_nonce( $nonce, $action ) {
		return wp_verify_nonce( sanitize_text_field( $nonce ), $action );
	}

	/**
	 * Get excerpt from text
	 *
	 * @param string $text   Text to excerpt
	 * @param int    $length Maximum length
	 * @param string $suffix Suffix for truncated text
	 * @return string
	 */
	public static function get_excerpt( $text, $length = 100, $suffix = '...' ) {
		if ( strlen( $text ) <= $length ) {
			return $text;
		}

		return substr( $text, 0, $length ) . $suffix;
	}

	/**
	 * Check if snippet file exists
	 *
	 * @param string $snippet_file Relative path to snippet file
	 * @return bool
	 */
	public static function snippet_file_exists( $snippet_file ) {
		$file_path = self::get_plugin_path() . 'includes/' . $snippet_file;
		return file_exists( $file_path );
	}

	/**
	 * Get WordPress version
	 *
	 * @return string
	 */
	public static function get_wp_version() {
		global $wp_version;
		return $wp_version;
	}

	/**
	 * Check if current WordPress version meets minimum requirement
	 *
	 * @param string $min_version Minimum required version
	 * @return bool
	 */
	public static function check_wp_version( $min_version = '5.0' ) {
		return version_compare( self::get_wp_version(), $min_version, '>=' );
	}

	/**
	 * Get memory usage information
	 *
	 * @return array Memory usage stats
	 */
	public static function get_memory_usage() {
		return array(
			'current' => self::format_file_size( memory_get_usage() ),
			'peak'    => self::format_file_size( memory_get_peak_usage() ),
			'limit'   => ini_get( 'memory_limit' ),
		);
	}

	/**
	 * Generate table row HTML
	 *
	 * @param array $cells Cell data
	 * @param array $attributes Row attributes
	 * @return string Table row HTML
	 */
	public static function generate_table_row( $cells, $attributes = array() ) {
		$attr_string = '';
		if ( ! empty( $attributes ) ) {
			foreach ( $attributes as $key => $value ) {
				$attr_string .= sprintf( ' %s="%s"', esc_attr( $key ), esc_attr( $value ) );
			}
		}

		$cells_html = '';
		foreach ( $cells as $cell ) {
			$cells_html .= '<td>' . wp_kses_post( $cell ) . '</td>';
		}

		return sprintf( '<tr%s>%s</tr>', $attr_string, $cells_html );
	}
}
