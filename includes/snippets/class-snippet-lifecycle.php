<?php
/**
 * Handles snippet lifecycle events (activation/deactivation).
 *
 * @package Lukic_Snippet_Codes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Lukic_Snippet_Lifecycle {

	/**
	 * Register hooks.
	 */
	public static function init() {
		add_action( 'update_option_Lukic_snippet_codes_options', array( __CLASS__, 'handle_snippet_changes' ), 10, 3 );
	}

	/**
	 * Respond to snippet state changes.
	 *
	 * @param mixed $old_value Previous option value.
	 * @param mixed $value     New option value.
	 */
	public static function handle_snippet_changes( $old_value, $value ) {
		$previous = is_array( $old_value ) ? $old_value : array();
		$current  = is_array( $value ) ? $value : array();
		$snippets = Lukic_Snippet_Registry::get_snippets();

		foreach ( $snippets as $snippet_id => $snippet ) {
			$was_active = isset( $previous[ $snippet_id ] ) && (int) $previous[ $snippet_id ] === 1;
			$is_active  = isset( $current[ $snippet_id ] ) && (int) $current[ $snippet_id ] === 1;

			if ( $was_active === $is_active ) {
				continue;
			}

			self::maybe_load_snippet_file( $snippet );

			$state = $is_active ? 'activate' : 'deactivate';
			self::run_lifecycle_callback( $snippet, $state, $snippet_id );
		}
	}

	/**
	 * Load snippet file to ensure lifecycle class/functions exist.
	 *
	 * @param array $snippet Snippet metadata.
	 */
	private static function maybe_load_snippet_file( $snippet ) {
		if ( empty( $snippet['file'] ) ) {
			return;
		}

		$file_path = Lukic_SNIPPET_CODES_PLUGIN_DIR . 'includes/' . $snippet['file'];
		if ( file_exists( $file_path ) ) {
			require_once $file_path;
		}
	}

	/**
	 * Run registered lifecycle callback if available.
	 *
	 * @param array  $snippet    Snippet metadata.
	 * @param string $hook       'activate' or 'deactivate'.
	 * @param string $snippet_id Snippet identifier.
	 */
	private static function run_lifecycle_callback( $snippet, $hook, $snippet_id ) {
		if ( empty( $snippet['lifecycle'][ $hook ] ) ) {
			return;
		}

		$callback = $snippet['lifecycle'][ $hook ];

		if ( is_callable( $callback ) ) {
			call_user_func( $callback, $snippet_id );
			return;
		}

		// Allow array format [className, method].
		if ( is_array( $callback ) && count( $callback ) === 2 ) {
			$class  = $callback[0];
			$method = $callback[1];
			if ( class_exists( $class ) && method_exists( $class, $method ) ) {
				call_user_func( array( $class, $method ), $snippet_id );
			}
		}
	}
}

Lukic_Snippet_Lifecycle::init();
