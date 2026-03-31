<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Snippet: Post Duplicator
 * Description: Adds a "Duplicate" link to all post types for quick duplication
 */

if ( ! function_exists( 'Lukic_duplicate_post_init' ) ) {
	/**
	 * Initialize the post duplicator functionality
	 */
	function Lukic_duplicate_post_init() {
		// Add duplicate link to standard posts and pages
		add_filter( 'post_row_actions', 'Lukic_add_duplicate_link', 10, 2 );
		add_filter( 'page_row_actions', 'Lukic_add_duplicate_link', 10, 2 );

		// Add duplicate link to ALL custom post types
		add_action( 'admin_init', 'Lukic_add_duplicate_link_to_cpt' );

		// Handle the duplicate action
		add_action( 'admin_action_Lukic_duplicate_post_as_draft', 'Lukic_duplicate_post_as_draft' );
	}
	Lukic_duplicate_post_init();

	/**
	 * Add duplicate link to all custom post types
	 */
	function Lukic_add_duplicate_link_to_cpt() {
		// Get all custom post types
		$custom_post_types = get_post_types(
			array(
				'public'   => true,
				'_builtin' => false,
			)
		);

		// Add the duplicate link filter to each custom post type
		foreach ( $custom_post_types as $cpt ) {
			add_filter( $cpt . '_row_actions', 'Lukic_add_duplicate_link', 10, 2 );
		}
	}

	/**
	 * Create a duplicate of a post or page
	 */
	function Lukic_duplicate_post_as_draft() {
		// Check if user has permissions
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( esc_html__( 'You do not have permission to duplicate this content.', 'lukic-code-snippets' ) );
		}

		// Verify nonce
		if ( ! isset( $_GET['duplicate_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['duplicate_nonce'] ) ), 'Lukic_duplicate_post' ) ) {
			wp_die( esc_html__( 'Security check failed. Please try again.', 'lukic-code-snippets' ) );
		}

		// Check for post ID
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( ! ( isset( $_GET['post'] ) || isset( $_POST['post'] ) ||
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			( isset( $_REQUEST['action'] ) && 'Lukic_duplicate_post_as_draft' == wp_unslash( $_REQUEST['action'] ) ) ) ) {
			wp_die( esc_html__( 'No post to duplicate has been provided.', 'lukic-code-snippets' ) );
		}

		// Get the original post ID
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$post_id = isset( $_GET['post'] ) ? absint( wp_unslash( $_GET['post'] ) ) : absint( wp_unslash( $_POST['post'] ) );

		// Get the original post data
		$post = get_post( $post_id );

		if ( ! $post ) {
			wp_die( esc_html__( 'Post creation failed, could not find original post.', 'lukic-code-snippets' ) );
		}

		// Get current user as author
		$current_user    = wp_get_current_user();
		$new_post_author = $current_user->ID;

		// Create duplicate post array
		$args = array(
			'comment_status' => $post->comment_status,
			'ping_status'    => $post->ping_status,
			'post_author'    => $new_post_author,
			'post_content'   => $post->post_content,
			'post_excerpt'   => $post->post_excerpt,
			'post_name'      => $post->post_name,
			'post_parent'    => $post->post_parent,
			'post_password'  => $post->post_password,
			'post_status'    => 'draft',
			'post_title'     => $post->post_title . ' (Copy)',
			'post_type'      => $post->post_type,
			'to_ping'        => $post->to_ping,
			'menu_order'     => $post->menu_order,
		);

		// Insert the post
		$new_post_id = wp_insert_post( $args );

		if ( is_wp_error( $new_post_id ) ) {
			wp_die( esc_html( $new_post_id->get_error_message() ) );
		}

		// Copy post taxonomies
		$taxonomies = get_object_taxonomies( $post->post_type );
		foreach ( $taxonomies as $taxonomy ) {
			$post_terms = wp_get_object_terms( $post_id, $taxonomy, array( 'fields' => 'slugs' ) );
			wp_set_object_terms( $new_post_id, $post_terms, $taxonomy, false );
		}

		// Copy post meta data
		global $wpdb;

		// Get all current post meta
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$post_meta_infos = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT meta_key, meta_value FROM $wpdb->postmeta WHERE post_id = %d",
				$post_id
			)
		);

		if ( ! empty( $post_meta_infos ) ) {
			$sql_query     = "INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value) ";
			$sql_query_sel = array();

			foreach ( $post_meta_infos as $meta_info ) {
				$meta_key = $meta_info->meta_key;

				// Skip the old slug meta key
				if ( '_wp_old_slug' == $meta_key ) {
					continue;
				}

				$meta_value = $meta_info->meta_value;

				$sql_query_sel[] = $wpdb->prepare(
					'SELECT %d, %s, %s',
					$new_post_id,
					$meta_key,
					$meta_value
				);
			}

			if ( ! empty( $sql_query_sel ) ) {
				$sql_query .= implode( ' UNION ALL ', $sql_query_sel );
				// The meta_key and meta_value are already safely passed into prepare() in the loop.
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->query( $sql_query );
			}
		}

		// Redirect to the edit screen for the new draft post
		wp_safe_redirect( admin_url( 'post.php?action=edit&post=' . $new_post_id ) );
		exit();
	}

	/**
	 * Add the duplicate link to post/page action rows
	 */
	function Lukic_add_duplicate_link( $actions, $post ) {
		if ( current_user_can( 'edit_posts' ) ) {
			$actions['duplicate'] = sprintf(
				'<a href="%s" title="%s" rel="permalink">%s</a>',
				wp_nonce_url(
					admin_url( 'admin.php?action=Lukic_duplicate_post_as_draft&post=' . $post->ID ),
					'Lukic_duplicate_post',
					'duplicate_nonce'
				),
				__( 'Duplicate this item', 'lukic-code-snippets' ),
				__( 'Duplicate', 'lukic-code-snippets' )
			);
		}
		return $actions;
	}
}
