<?php
/**
 * Show Custom Taxonomy Filters
 *
 * Shows additional filter dropdowns on list tables for hierarchical and non-hierarchical
 * custom taxonomies. Works for both default and custom post types.
 *
 * @package Lukic_Snippet_Codes
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add custom taxonomy filters to admin list tables
 */
function Lukic_add_taxonomy_filters() {
	global $typenow;

	// Get all taxonomies.
	$taxonomies = get_taxonomies( array( 'show_ui' => true ), 'objects' );

	if ( empty( $taxonomies ) ) {
		return;
	}

	foreach ( $taxonomies as $taxonomy ) {
		// Skip categories and tags as WordPress already has filters for these.
		if ( in_array( $taxonomy->name, array( 'category', 'post_tag' ), true ) ) {
			continue;
		}

		// Check if this taxonomy is registered for the current post type.
		$post_types = $taxonomy->object_type;

		if ( empty( $post_types ) || ! in_array( $typenow, $post_types, true ) ) {
			continue;
		}

		// Get the taxonomy terms.
		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy->name,
				'hide_empty' => false,
			)
		);

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			continue;
		}

		// Display filter dropdown.
		echo '<select name="' . esc_attr( $taxonomy->name ) . '" id="' . esc_attr( $taxonomy->name ) . '" class="postform">';
		/* translators: %s: Taxonomy label (e.g., Categories, Tags) */
		echo '<option value="">' . sprintf( esc_html__( 'All %s', 'lukic-code-snippets' ), esc_html( $taxonomy->label ) ) . '</option>';

		Lukic_display_taxonomy_terms( $terms, $taxonomy );

		echo '</select>';
	}
}
add_action( 'restrict_manage_posts', 'Lukic_add_taxonomy_filters' );

/**
 * Helper function to display taxonomy terms in a hierarchical format
 *
 * @param array  $terms     Array of term objects.
 * @param object $taxonomy  Taxonomy object.
 * @param int    $parent    Parent term ID.
 * @param int    $level     Current hierarchy level.
 */
function Lukic_display_taxonomy_terms( $terms, $taxonomy, $parent = 0, $level = 0 ) {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$selected_raw = isset( $_GET[ $taxonomy->name ] ) ? sanitize_text_field( wp_unslash( $_GET[ $taxonomy->name ] ) ) : '';
	$term_slugs   = wp_list_pluck( $terms, 'slug' );
	$selected     = in_array( $selected_raw, $term_slugs, true ) ? $selected_raw : '';

	foreach ( $terms as $term ) {
		if ( (int) $term->parent === (int) $parent ) {
			// Create indentation for hierarchical terms.
			$indent = str_repeat( '- ', max( 0, (int) $level ) );

			// Display the term as an option.
			echo '<option value="' . esc_attr( $term->slug ) . '" ' . selected( $selected, $term->slug, false ) . '>'
				. esc_html( $indent . $term->name )
				. ' (' . esc_html( (string) intval( $term->count ) ) . ')'
				. '</option>';

			// Display child terms with increased level.
			Lukic_display_taxonomy_terms( $terms, $taxonomy, $term->term_id, $level + 1 );
		}
	}
}
