<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Snippet: Search Posts by Slug
 * Description: Enhances WordPress admin search to include post slugs in search results for both regular posts and custom post types, with multilingual support
 */

if ( ! function_exists( 'Lukic_search_by_slug_init' ) ) {
	/**
	 * Initialize the search by slug functionality
	 */
	function Lukic_search_by_slug_init() {
		// Modify search query to include slugs for posts and pages
		add_action( 'pre_get_posts', 'Lukic_include_slug_in_search' );
	}
	Lukic_search_by_slug_init();

	/**
	 * Modify search query to include post/page slugs
	 *
	 * @param WP_Query $query The WP_Query instance
	 */
	function Lukic_include_slug_in_search( $query ) {
		// Only run in admin area and only for the main query
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		// Make sure this is a search query
		$search_term = $query->get( 's' );
		if ( empty( $search_term ) ) {
			return;
		}

		// Get current screen information
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		// Only apply to post listing screens
		if ( ! $screen || $screen->base !== 'edit' ) {
			return;
		}

		// Remove the default search
		$query->set( 's', '' );

		// Check for multilingual plugins
		$lang_term = '';
		if ( function_exists( 'icl_object_id' ) && defined( 'ICL_LANGUAGE_CODE' ) ) {
			// WPML
			$lang_term = ICL_LANGUAGE_CODE;
		} elseif ( function_exists( 'pll_current_language' ) ) {
			// Polylang
			$lang_term = pll_current_language();
		} elseif ( function_exists( 'qtranxf_getLanguage' ) ) {
			// qTranslate
			$lang_term = qtranxf_getLanguage();
		}

		// Set up the where clause filter for the search
		add_filter(
			'posts_where',
			function ( $where ) use ( $search_term, $query ) {
				global $wpdb;

				// Only apply to our specific query
				if ( $query->is_main_query() ) {
					// Create SQL for searching title, content, excerpt & slug
					$search_sql = $wpdb->prepare(
						"({$wpdb->posts}.post_title LIKE %s 
                    OR {$wpdb->posts}.post_content LIKE %s 
                    OR {$wpdb->posts}.post_excerpt LIKE %s 
                    OR {$wpdb->posts}.post_name LIKE %s)",
						'%' . $wpdb->esc_like( $search_term ) . '%',
						'%' . $wpdb->esc_like( $search_term ) . '%',
						'%' . $wpdb->esc_like( $search_term ) . '%',
						'%' . $wpdb->esc_like( $search_term ) . '%'
					);

					// Add our search conditions to the where clause
					$where = preg_replace(
						"/\(\s*{$wpdb->posts}.post_title\s+LIKE\s*(\'[^\']+\')\s*\)/",
						$search_sql,
						$where
					);

					// If no existing search clause was found, add ours
					if ( $where === preg_replace( "/\(\s*{$wpdb->posts}.post_title\s+LIKE\s*(\'[^\']+\')\s*\)/", 'XXX', $where ) ) {
						$where .= ' AND ' . $search_sql;
					}
				}

				return $where;
			}
		);

		// Add language filter if a multilingual plugin is active
		if ( ! empty( $lang_term ) ) {
			// Check which taxonomy to use based on the active plugin
			$taxonomy = 'language';
			if ( function_exists( 'pll_current_language' ) ) {
				// Polylang uses 'language' taxonomy
				$taxonomy = 'language';
			} elseif ( function_exists( 'icl_object_id' ) ) {
				// WPML typically uses 'icl_translations' table, but we'll just check if posts have language term
				// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
				$taxonomy = apply_filters( 'wpml_current_language', 'language' );
			}

			// Add tax query for the language
			$tax_query   = $query->get( 'tax_query', array() );
			$tax_query[] = array(
				'taxonomy' => $taxonomy,
				'field'    => 'slug',
				'terms'    => $lang_term,
			);
			$query->set( 'tax_query', $tax_query );
		}

		// Hook to clean up our filter
		add_action(
			'posts_selection',
			function () use ( $query ) {
				// Remove filter after it's been used to avoid affecting other queries
				remove_all_filters( 'posts_where' );
			}
		);
	}
}
