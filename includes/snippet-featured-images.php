<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Snippet: Show Featured Images in Admin Tables
 * Description: Adds a featured image column to admin tables for posts, pages, and custom post types
 */

if ( ! function_exists( 'Lukic_show_featured_images_init' ) ) {
	/**
	 * Initialize the featured image column functionality
	 */
	function Lukic_show_featured_images_init() {
		// For post types
		add_action( 'current_screen', 'Lukic_setup_featured_image_columns' );

		// Add styles for the image column
		add_action( 'admin_enqueue_scripts', 'Lukic_featured_image_column_style' );

		// Enqueue scripts
		add_action( 'admin_enqueue_scripts', 'Lukic_featured_image_admin_scripts' );

		// AJAX Handlers
		add_action( 'wp_ajax_lukic_update_featured_image', 'Lukic_ajax_update_featured_image' );
		add_action( 'wp_ajax_lukic_update_taxonomy_image', 'Lukic_ajax_update_taxonomy_image' );
	}
	Lukic_show_featured_images_init();

	/**
	 * Enqueue admin scripts for the featured image column
	 */
	function Lukic_featured_image_admin_scripts( $hook ) {
		// Only load on edit.php (post list) and edit-tags.php (taxonomy list)
		if ( 'edit.php' !== $hook && 'edit-tags.php' !== $hook ) {
			return;
		}

		wp_enqueue_media();
		wp_enqueue_script(
			'lukic-fi-admin',
			plugins_url( 'assets/js/featured-image-admin.js', __DIR__ ),
			array( 'jquery' ),
			'1.0.0',
			true
		);

		wp_localize_script(
			'lukic-fi-admin',
			'lukic_fi_admin',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'lukic_fi_admin_nonce' ),
				'i18n'     => array(
					'choose_image'   => __( 'Choose Featured Image', 'lukic-code-snippets' ),
					'set_image'      => __( 'Set featured image', 'lukic-code-snippets' ),
					'confirm_remove' => __( 'Are you sure you want to remove this featured image?', 'lukic-code-snippets' ),
					'error'          => __( 'An error occurred. Please try again.', 'lukic-code-snippets' ),
				),
			)
		);
	}

	/**
	 * Setup the featured image columns based on current screen
	 */
	function Lukic_setup_featured_image_columns() {
		$screen = get_current_screen();

		// Handle post types (only on list screens, not edit screens)
		if ( $screen->base === 'edit' && post_type_supports( $screen->post_type, 'thumbnail' ) ) {
			// Add the column (priority 5 to ensure it's early in the list)
			add_filter( 'manage_' . $screen->post_type . '_posts_columns', 'Lukic_add_featured_image_column', 5 );

			// Fill the column
			add_action( 'manage_' . $screen->post_type . '_posts_custom_column', 'Lukic_show_featured_image_column_content', 10, 2 );
		}

		// Handle taxonomies
		if ( $screen->base === 'edit-tags' ) {
			// Add the column
			add_filter( 'manage_edit-' . $screen->taxonomy . '_columns', 'Lukic_add_featured_image_column', 5 );

			// Fill the column
			add_filter( 'manage_' . $screen->taxonomy . '_custom_column', 'Lukic_show_taxonomy_featured_image_column_content', 10, 3 );
		}
	}

	/**
	 * Add featured image column
	 */
	function Lukic_add_featured_image_column( $columns ) {
		// New approach - insert after ID column if it exists, otherwise after checkbox
		$new_columns = array();

		foreach ( $columns as $key => $title ) {
			$new_columns[ $key ] = $title;

			// Add our column after the ID column if it exists
			if ( $key === 'Lukic_id' ) {
				$new_columns['Lukic_featured_image'] = __( 'Image', 'lukic-code-snippets' );
			}
		}

		// If there's no ID column, add after checkbox
		if ( ! isset( $columns['Lukic_id'] ) ) {
			if ( isset( $columns['cb'] ) ) {
				// Get checkbox
				$cb = $new_columns['cb'];
				unset( $new_columns['cb'] );

				// Rebuild columns with our column after checkbox
				$new_columns = array(
					'cb'                     => $cb,
					'Lukic_featured_image' => __( 'Image', 'lukic-code-snippets' ),
				) + $new_columns;
			} else {
				// If no checkbox either, add to beginning
				$new_columns = array( 'Lukic_featured_image' => __( 'Image', 'lukic-code-snippets' ) ) + $new_columns;
			}
		}

		return $new_columns;
	}

	/**
	 * Display the featured image for post types
	 */
	function Lukic_show_featured_image_column_content( $column_name, $post_id ) {
		if ( 'Lukic_featured_image' === $column_name ) {
			echo '<div class="lukic-featured-image-wrapper" data-object-id="' . esc_attr( $post_id ) . '" data-object-type="post" title="' . esc_attr__( 'Click to edit image', 'lukic-code-snippets' ) . '">';
			
			if ( has_post_thumbnail( $post_id ) ) {
				$thumbnail = get_the_post_thumbnail( $post_id, array( 80, 80 ) );
				echo wp_kses_post( $thumbnail );
				echo '<span class="lukic-fi-overlay-text">' . esc_html__( 'Edit', 'lukic-code-snippets' ) . '</span>';
				echo '<button type="button" class="lukic-fi-remove" title="' . esc_attr__( 'Remove image', 'lukic-code-snippets' ) . '">&#215;</button>';
			} else {
				$no_image_url = plugins_url( 'assets/icons/no_image.svg', __DIR__ );
				echo '<img src="' . esc_url( $no_image_url ) . '" alt="No image" class="Lukic-no-image-placeholder" />';
				echo '<span class="lukic-fi-overlay-text">' . esc_html__( 'Upload', 'lukic-code-snippets' ) . '</span>';
				echo '<button type="button" class="lukic-fi-remove" style="display:none;" title="' . esc_attr__( 'Remove image', 'lukic-code-snippets' ) . '">&#215;</button>';
			}
			
			echo '<div class="lukic-fi-spinner"></div>';
			echo '</div>';
		}
	}

	/**
	 * Display the featured image for taxonomies
	 */
	function Lukic_show_taxonomy_featured_image_column_content( $content, $column_name, $term_id ) {
		if ( 'Lukic_featured_image' === $column_name ) {
			$html = '<div class="lukic-featured-image-wrapper" data-object-id="' . esc_attr( $term_id ) . '" data-object-type="term" title="' . esc_attr__( 'Click to edit image', 'lukic-code-snippets' ) . '">';
			$has_image = false;

			// Try common term meta fields
			$image_field_names = array(
				'thumbnail_id',
				'image',
				'term_image',
				'featured_image',
				'category_image',
				'tax_image',
			);

			foreach ( $image_field_names as $field ) {
				$meta_value = get_term_meta( $term_id, $field, true );
				if ( ! empty( $meta_value ) && is_numeric( $meta_value ) ) {
					$image = wp_get_attachment_image( $meta_value, array( 50, 50 ) );
					if ( $image ) {
						$html .= $image;
						$has_image = true;
						break;
					}
				}
			}

			if ( ! $has_image ) {
				$no_image_url = plugins_url( 'assets/icons/no_image.svg', __DIR__ );
				$html .= '<img src="' . esc_url( $no_image_url ) . '" width="50" height="50" alt="No image" class="Lukic-no-image-placeholder" />';
			}

			$overlay_text = $has_image ? __( 'Edit', 'lukic-code-snippets' ) : __( 'Upload', 'lukic-code-snippets' );
			$remove_style = $has_image ? '' : 'style="display:none;"';

			$html .= '<span class="lukic-fi-overlay-text">' . esc_html( $overlay_text ) . '</span>';
			$html .= '<button type="button" class="lukic-fi-remove" ' . $remove_style . ' title="' . esc_attr__( 'Remove image', 'lukic-code-snippets' ) . '">&#215;</button>';
			$html .= '<div class="lukic-fi-spinner"></div>';
			$html .= '</div>';

			return $html;
		}

		return $content;
	}

	/**
	 * Add custom styling for the featured image column
	 */
	function Lukic_featured_image_column_style() {
		wp_add_inline_style( 'wp-admin', '
			.column-Lukic_featured_image {
				width: 80px !important;
				text-align: center !important;
				vertical-align: middle !important;
			}
			.lukic-featured-image-wrapper {
				position: relative;
				display: inline-block;
				cursor: pointer;
				border-radius: 3px;
				border: 1px solid #ddd;
				background: #f9f9f9;
				padding: 2px;
				overflow: hidden;
				width: 76px;
				height: 76px;
				line-height: 72px;
				box-sizing: border-box;
			}
			.lukic-featured-image-wrapper img {
				max-width: 100%;
				max-height: 100%;
				display: inline-block;
				vertical-align: middle;
			}
			.lukic-featured-image-wrapper .Lukic-no-image-placeholder {
				opacity: 0.5;
			}
			.lukic-fi-overlay-text {
				position: absolute;
				bottom: 0;
				left: 0;
				right: 0;
				background: rgba(0, 0, 0, 0.6);
				color: #fff;
				font-size: 11px;
				line-height: 1.5;
				padding: 2px 0;
				text-align: center;
				opacity: 0;
				transition: opacity 0.2s;
			}
			.lukic-featured-image-wrapper:hover .lukic-fi-overlay-text {
				opacity: 1;
			}
			.lukic-fi-remove {
				position: absolute;
				top: 0;
				right: 0;
				background: #d63638;
				color: #fff;
				border: none;
				width: 20px;
				height: 20px;
				line-height: 18px;
				text-align: center;
				cursor: pointer;
				font-size: 16px;
				font-weight: bold;
				opacity: 0;
				transition: opacity 0.2s;
				padding: 0;
				border-bottom-left-radius: 3px;
			}
			.lukic-fi-remove:hover {
				background: #b32d2e;
			}
			.lukic-featured-image-wrapper:hover .lukic-fi-remove {
				opacity: 1;
			}
			.lukic-fi-spinner {
				display: none;
				position: absolute;
				top: 0; left: 0; right: 0; bottom: 0;
				background: rgba(255,255,255,0.8);
			}
			.lukic-fi-spinner::after {
				content: "";
				position: absolute;
				top: 50%;
				left: 50%;
				margin-top: -10px;
				margin-left: -10px;
				width: 20px;
				height: 20px;
				border: 2px solid #ccc;
				border-top-color: #007cba;
				border-radius: 50%;
				animation: lukic-fi-spin 1s linear infinite;
			}
			@keyframes lukic-fi-spin {
				to { transform: rotate(360deg); }
			}
			.lukic-featured-image-wrapper.loading .lukic-fi-spinner {
				display: block;
			}
			.widefat .column-Lukic_featured_image {
				display: table-cell !important;
			}
		' );
	}

	/**
	 * AJAX handler to update post featured image
	 */
	function Lukic_ajax_update_featured_image() {
		check_ajax_referer( 'lukic_fi_admin_nonce', 'nonce' );

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$post_id       = isset( $_POST['object_id'] ) ? absint( wp_unslash( $_POST['object_id'] ) ) : 0;
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$attachment_id = isset( $_POST['attachment_id'] ) ? absint( wp_unslash( $_POST['attachment_id'] ) ) : 0;

		if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'lukic-code-snippets' ) ) );
		}

		if ( $attachment_id > 0 ) {
			$attachment = get_post( $attachment_id );
			if ( ! $attachment || 'attachment' !== $attachment->post_type || ! wp_attachment_is_image( $attachment_id ) || ! current_user_can( 'edit_post', $attachment_id ) ) {
				wp_send_json_error( array( 'message' => __( 'Invalid image selection.', 'lukic-code-snippets' ) ) );
			}

			set_post_thumbnail( $post_id, $attachment_id );
			$image_url = wp_get_attachment_image_url( $attachment_id, array( 80, 80 ) );
		} else {
			delete_post_thumbnail( $post_id );
			$image_url = plugins_url( 'assets/icons/no_image.svg', __DIR__ );
		}

		wp_send_json_success( array( 'image_url' => $image_url ) );
	}

	/**
	 * AJAX handler to update taxonomy featured image
	 */
	function Lukic_ajax_update_taxonomy_image() {
		check_ajax_referer( 'lukic_fi_admin_nonce', 'nonce' );

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$term_id       = isset( $_POST['object_id'] ) ? absint( wp_unslash( $_POST['object_id'] ) ) : 0;
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$attachment_id = isset( $_POST['attachment_id'] ) ? absint( wp_unslash( $_POST['attachment_id'] ) ) : 0;

		if ( ! $term_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid Term ID.', 'lukic-code-snippets' ) ) );
		}

		// Capabilities check depends on taxonomy... fallback to edit_terms if custom check fails.
		$term = get_term( $term_id );
		if ( ! $term || is_wp_error( $term ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid term.', 'lukic-code-snippets' ) ) );
		}
		
		$tax = get_taxonomy( $term->taxonomy );
		if ( ! $tax || ( ! current_user_can( 'edit_term', $term_id ) && ! current_user_can( $tax->cap->edit_terms ) ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'lukic-code-snippets' ) ) );
		}

		// Try to find if an existing field is used
		$image_field_names = array( 'thumbnail_id', 'image', 'term_image', 'featured_image', 'category_image', 'tax_image' );
		
		if ( $attachment_id > 0 ) {
			$attachment = get_post( $attachment_id );
			if ( ! $attachment || 'attachment' !== $attachment->post_type || ! wp_attachment_is_image( $attachment_id ) || ! current_user_can( 'edit_post', $attachment_id ) ) {
				wp_send_json_error( array( 'message' => __( 'Invalid image selection.', 'lukic-code-snippets' ) ) );
			}

			$field_to_update = 'thumbnail_id';
			foreach ( $image_field_names as $field ) {
				// Search if any metadata key already exists
				$meta_value = get_term_meta( $term_id, $field, true );
				if ( ! empty( $meta_value ) ) {
					$field_to_update = $field;
					break;
				}
			}
			update_term_meta( $term_id, $field_to_update, $attachment_id );
			$image_url = wp_get_attachment_image_url( $attachment_id, array( 50, 50 ) );
		} else {
			// Delete all known possible image fields to be safe when removing
			foreach ( $image_field_names as $field ) {
				delete_term_meta( $term_id, $field );
			}
			$image_url = plugins_url( 'assets/icons/no_image.svg', __DIR__ );
		}

		wp_send_json_success( array( 'image_url' => $image_url ) );
	}
}
