<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Snippet: Show IDs in Admin Tables
 * Description: Adds an ID column to admin tables for posts, pages, custom post types, and taxonomies
 */

if ( ! function_exists( 'Lukic_show_ids_in_admin_tables_init' ) ) {
	/**
	 * Initialize the ID column functionality
	 */
	function Lukic_show_ids_in_admin_tables_init() {
		// Show IDs for all post types (posts, pages, and CPTs)
		add_action( 'admin_init', 'Lukic_add_id_column_to_post_types' );

		// Show IDs for all taxonomies (categories, tags, and custom taxonomies)
		add_action( 'admin_init', 'Lukic_add_id_column_to_taxonomies' );
	}
	Lukic_show_ids_in_admin_tables_init();

	/**
	 * Add ID column to all post types
	 */
	function Lukic_add_id_column_to_post_types() {
		// Get all post types
		$post_types = get_post_types( array( 'show_ui' => true ), 'names' );

		foreach ( $post_types as $post_type ) {
			// Add the column to each post type
			add_filter( "manage_{$post_type}_posts_columns", 'Lukic_add_id_column', 5 );

			// Fill the column with the ID value
			add_action( "manage_{$post_type}_posts_custom_column", 'Lukic_show_id_column_content', 5, 2 );

			// Make the column sortable
			add_filter( "manage_edit-{$post_type}_sortable_columns", 'Lukic_make_id_column_sortable' );
		}
	}

	/**
	 * Add ID column to all taxonomies
	 */
	function Lukic_add_id_column_to_taxonomies() {
		// Get all taxonomies
		$taxonomies = get_taxonomies( array( 'show_ui' => true ), 'names' );

		foreach ( $taxonomies as $taxonomy ) {
			// Add the column to each taxonomy
			add_filter( "manage_edit-{$taxonomy}_columns", 'Lukic_add_id_column', 5 );

			// Fill the column with the ID value for taxonomies
			add_filter( "manage_{$taxonomy}_custom_column", 'Lukic_show_taxonomy_id_column_content', 5, 3 );
		}
	}

	/**
	 * Add ID column to the columns array - right after the checkbox column
	 */
	function Lukic_add_id_column( $columns ) {
		$new_columns = array();

		// Insert the ID column after the checkbox (cb) column
		foreach ( $columns as $key => $title ) {
			$new_columns[ $key ] = $title;

			// After the checkbox column, add our ID column
			if ( $key === 'cb' ) {
				$new_columns['Lukic_id'] = __( 'ID', 'lukic-code-snippets' );
			}
		}

		// If there's no checkbox column, add the ID column at the beginning
		if ( ! isset( $columns['cb'] ) ) {
			$new_columns = array( 'Lukic_id' => __( 'ID', 'lukic-code-snippets' ) ) + $new_columns;
		}

		return $new_columns;
	}

	/**
	 * Display the ID value for post types
	 */
	function Lukic_show_id_column_content( $column_name, $post_id ) {
		if ( 'Lukic_id' === $column_name ) {
			echo '<strong>' . esc_html( intval( $post_id ) ) . '</strong>';
		}
	}

	/**
	 * Display the ID value for taxonomies
	 */
	function Lukic_show_taxonomy_id_column_content( $content, $column_name, $term_id ) {
		if ( 'Lukic_id' === $column_name ) {
			return '<strong>' . esc_html( intval( $term_id ) ) . '</strong>';
		}
		return $content;
	}

	/**
	 * Make the ID column sortable
	 */
	function Lukic_make_id_column_sortable( $columns ) {
		$columns['Lukic_id'] = 'ID';
		return $columns;
	}

	/**
	 * Add custom styling for the ID column
	 */
	function Lukic_id_column_style() {
		wp_add_inline_style( 'wp-admin', '
			.column-Lukic_id {
				width: 70px;
				text-align: center;
			}
			.fixed .column-Lukic_id {
				width: 70px;
				text-align: left;
			}
		' );
	}
	add_action( 'admin_enqueue_scripts', 'Lukic_id_column_style' );
}
