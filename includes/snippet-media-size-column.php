<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Snippet: Media Size Column
 * Description: Adds a size column to the media library in list view that displays file size and allows sorting
 */

/**
 * Add the size column to the media library list
 *
 * @param array $columns The current columns
 * @return array Modified columns
 */
function Lukic_media_size_column( $columns ) {
	// Add size column after the 'author' column
	$new_columns = array();

	foreach ( $columns as $key => $value ) {
		$new_columns[ $key ] = $value;

		if ( $key === 'author' ) {
			$new_columns['Lukic_file_size'] = __( 'Size', 'lukic-code-snippets' );
		}
	}

	return $new_columns;
}
add_filter( 'manage_media_columns', 'Lukic_media_size_column' );
add_filter( 'manage_upload_sortable_columns', 'Lukic_media_size_sortable_column' );

/**
 * Make the size column sortable
 *
 * @param array $columns The current sortable columns
 * @return array Modified sortable columns
 */
function Lukic_media_size_sortable_column( $columns ) {
	$columns['Lukic_file_size'] = 'Lukic_file_size';
	return $columns;
}

/**
 * Populate the size column
 *
 * @param string $column_name Name of the column
 * @param int    $post_id ID of the current attachment
 */
function Lukic_media_size_column_content( $column_name, $post_id ) {
	if ( $column_name !== 'Lukic_file_size' ) {
		return;
	}

	// Get file path
	$file_path = get_attached_file( $post_id );

	if ( ! file_exists( $file_path ) ) {
		echo '—';
		return;
	}

	// Get file size
	$file_size = filesize( $file_path );

	// Store file size as post meta for sorting
	update_post_meta( $post_id, 'Lukic_file_size', $file_size );

	// Format size for display
	echo esc_html( Lukic_format_file_size( $file_size ) );
}
add_action( 'manage_media_custom_column', 'Lukic_media_size_column_content', 10, 2 );

/**
 * Format file size to human-readable format
 *
 * @param int $bytes File size in bytes
 * @param int $decimals Number of decimal places
 * @return string Formatted size
 */
function Lukic_format_file_size( $bytes, $decimals = 2 ) {
	$size   = array( 'B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB' );
	$factor = floor( ( strlen( $bytes ) - 1 ) / 3 );

	if ( $factor === 0 ) {
		$decimals = 0; // No decimals for bytes
	}

	return sprintf( "%.{$decimals}f", $bytes / pow( 1024, $factor ) ) . ' ' . $size[ $factor ];
}

/**
 * Add custom orderby clause for size sorting
 *
 * @param array $vars The query variables
 * @return array Modified query variables
 */
function Lukic_media_size_orderby( $vars ) {
	if ( ! is_admin() || ! isset( $vars['orderby'] ) ) {
		return $vars;
	}

	// Check if we're on the media library screen and ordering by file size
	if ( $vars['orderby'] === 'Lukic_file_size' ) {
		// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		$vars = array_merge(
			$vars,
			array(
				'meta_key' => 'Lukic_file_size',
				'orderby'  => 'meta_value_num',
			)
		);
	}

	return $vars;
}
add_filter( 'request', 'Lukic_media_size_orderby' );

/**
 * Enqueue custom CSS for the size column.
 */
function Lukic_media_size_column_style() {
	if ( ! function_exists( 'get_current_screen' ) ) {
		return;
	}

	$screen = get_current_screen();

	if ( ! $screen || $screen->id !== 'upload' ) {
		return;
	}

	wp_enqueue_style(
		'Lukic-media-size-column',
		plugin_dir_url( __DIR__ ) . 'assets/css/media-size-column.css',
		array(),
		Lukic_SNIPPET_CODES_VERSION
	);
}
add_action( 'admin_enqueue_scripts', 'Lukic_media_size_column_style' );
