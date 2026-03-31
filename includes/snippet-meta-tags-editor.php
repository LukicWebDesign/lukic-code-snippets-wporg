<?php
/**
 * Snippet: Meta Tags Editor
 * Description: Provides a table interface to edit meta tags (title, description) for all pages on the website
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add submenu for Meta Tags Editor
 */
function Lukic_meta_tags_editor_menu() {
	add_submenu_page(
		'lukic-code-snippets',
		__( 'Meta Tags Editor', 'lukic-code-snippets' ),
		__( 'Meta Tags Editor', 'lukic-code-snippets' ),
		'manage_options',
		'lukic-meta-tags-editor',
		'Lukic_meta_tags_editor_page'
	);
}
add_action( 'admin_menu', 'Lukic_meta_tags_editor_menu' );

/**
 * Localize data for the meta tags editor when on its admin page.
 */
function Lukic_meta_tags_editor_localize( $hook ) {

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$current_page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
	if ( $current_page === 'lukic-meta-tags-editor' ) {
		wp_localize_script(
			'Lukic-meta-tags',
			'LukicMetaTagsEditor',
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'Lukic_meta_tags_editor_nonce' ),
			)
		);
	}
}
add_action( 'admin_enqueue_scripts', 'Lukic_meta_tags_editor_localize' );
/**
 * Check if Yoast SEO plugin is active
 */
function Lukic_is_yoast_active() {
	return defined( 'WPSEO_VERSION' );
}

/**
 * Check if Rank Math plugin is active
 */
function Lukic_is_rank_math_active() {
	return class_exists( 'RankMath' );
}

/**
 * Get meta title for a post
 */
function Lukic_get_meta_title( $post_id ) {
	// Default WordPress meta title
	$meta_title = get_post_meta( $post_id, '_meta_title', true );

	// Check for Yoast SEO
	if ( Lukic_is_yoast_active() && empty( $meta_title ) ) {
		$meta_title = get_post_meta( $post_id, '_yoast_wpseo_title', true );
	}

	// Check for Rank Math
	if ( Lukic_is_rank_math_active() && empty( $meta_title ) ) {
		$meta_title = get_post_meta( $post_id, 'rank_math_title', true );
	}

	// If still empty, use post title
	if ( empty( $meta_title ) ) {
		$meta_title = get_the_title( $post_id );
	}

	return $meta_title;
}

/**
 * Get meta description for a post
 */
function Lukic_get_meta_description( $post_id ) {
	// Default WordPress meta description
	$meta_description = get_post_meta( $post_id, '_meta_description', true );

	// Check for Yoast SEO
	if ( Lukic_is_yoast_active() && empty( $meta_description ) ) {
		$meta_description = get_post_meta( $post_id, '_yoast_wpseo_metadesc', true );
	}

	// Check for Rank Math
	if ( Lukic_is_rank_math_active() && empty( $meta_description ) ) {
		$meta_description = get_post_meta( $post_id, 'rank_math_description', true );
	}

	// If still empty, generate from content
	if ( empty( $meta_description ) ) {
		$post = get_post( $post_id );
		if ( $post ) {
			$excerpt = wp_strip_all_tags( $post->post_excerpt );
			if ( empty( $excerpt ) ) {
				$excerpt = wp_strip_all_tags( $post->post_content );
			}
			$meta_description = wp_trim_words( $excerpt, 30, '...' );
		}
	}

	return $meta_description;
}

/**
 * Get all URLs from the website including posts, pages, custom post types, and taxonomies
 */
function Lukic_get_all_urls() {
	$urls = array();

	// Get all public post types
	$post_types = get_post_types( array( 'public' => true ), 'names' );

	// Get posts, pages, and CPTs
	$args = array(
		'post_type'      => $post_types,
		'post_status'    => 'publish',
		'posts_per_page' => -1,
	);

	$query = new WP_Query( $args );

	if ( $query->have_posts() ) {
		while ( $query->have_posts() ) {
			$query->the_post();
			$post_id = get_the_ID();
			$urls[]  = array(
				'id'          => $post_id,
				'url'         => get_permalink( $post_id ),
				'title'       => Lukic_get_meta_title( $post_id ),
				'description' => Lukic_get_meta_description( $post_id ),
				'type'        => 'post',
				'post_type'   => get_post_type( $post_id ),
			);
		}
	}
	wp_reset_postdata();

	// Get all taxonomies
	$taxonomies = get_taxonomies( array( 'public' => true ), 'names' );

	foreach ( $taxonomies as $taxonomy ) {
		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => true,
				'number'     => 0, // Get all terms
			)
		);

		// Skip if no terms or error
		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			continue;
		}

		foreach ( $terms as $term ) {
			$term_id  = $term->term_id;
			$term_url = get_term_link( $term );

			// Skip if there was an error
			if ( is_wp_error( $term_url ) ) {
				continue;
			}

			// Get meta title and description for taxonomy
			$meta_title       = '';
			$meta_description = '';

			// Check for Yoast SEO
			if ( Lukic_is_yoast_active() ) {
				$meta_title       = get_term_meta( $term_id, '_yoast_wpseo_title', true );
				$meta_description = get_term_meta( $term_id, '_yoast_wpseo_metadesc', true );
			}

			// Check for Rank Math
			if ( Lukic_is_rank_math_active() && ( empty( $meta_title ) || empty( $meta_description ) ) ) {
				$meta_title       = get_term_meta( $term_id, 'rank_math_title', true );
				$meta_description = get_term_meta( $term_id, 'rank_math_description', true );
			}

			// If still empty, use term name
			if ( empty( $meta_title ) ) {
				$meta_title = $term->name;
			}

			// If description is empty, use term description
			if ( empty( $meta_description ) ) {
				$meta_description = $term->description;
			}

			$urls[] = array(
				'id'          => 'tax_' . $term_id,
				'url'         => $term_url,
				'title'       => $meta_title,
				'description' => $meta_description,
				'type'        => 'taxonomy',
				'taxonomy'    => $taxonomy,
			);
		}
	}

	return $urls;
}

/**
 * Render the Meta Tags Editor page
 */
function Lukic_meta_tags_editor_page() {
	// Get all URLs
	$all_urls   = Lukic_get_all_urls();
	$urls_count = count( $all_urls );

	// Determine SEO plugin status
	$yoast_active     = Lukic_is_yoast_active();
	$rank_math_active = Lukic_is_rank_math_active();

	// Include the header partial
	// Header component is already loaded in main plugin file

	// Prepare stats for header
	$stats = array(
		array(
			'count' => $urls_count,
			'label' => 'URLS',
		),
		array(
			'count' => ( $yoast_active || $rank_math_active ) ? 'Yoast' : 'Rank Math',
			'label' => 'Active',
		),
	);

	?>
	<div class="wrap Lukic-admin-page">
	<?php Lukic_display_header( __( 'Meta Tags Editor', 'lukic-code-snippets' ), $stats ); ?>
		<div class="Lukic-container">
			<div class="Lukic-content-wrapper">
				<div class="Lukic-content">
					<div class="Lukic-card">
						<div class="Lukic-card-header">
							<h2><?php echo esc_html__( 'Meta Tags Editor', 'lukic-code-snippets' ); ?></h2>
							<p class="Lukic-description"><?php echo esc_html__( 'Edit meta tags for all pages, posts, and taxonomies on your website. Changes are saved automatically when you click outside of the edited field.', 'lukic-code-snippets' ); ?></p>
							
							<?php if ( $yoast_active ) : ?>
								<div class="Lukic-notice Lukic-notice-info">
									<p><?php echo esc_html__( 'Yoast SEO detected. Meta tags will be edited using Yoast SEO data.', 'lukic-code-snippets' ); ?></p>
								</div>
							<?php elseif ( $rank_math_active ) : ?>
								<div class="Lukic-notice Lukic-notice-info">
									<p><?php echo esc_html__( 'Rank Math detected. Meta tags will be edited using Rank Math data.', 'lukic-code-snippets' ); ?></p>
								</div>
							<?php endif; ?>
							
							<div class="Lukic-actions-bar">
								<button id="export-meta-tags" class="Lukic-btn">
									<span class="dashicons dashicons-download"></span> <?php echo esc_html__( 'Export CSV', 'lukic-code-snippets' ); ?>
								</button>
								
								<div class="Lukic-import-container">
									<button id="import-meta-tags-btn" class="Lukic-btn Lukic-btn-secondary">
										<span class="dashicons dashicons-upload"></span> <?php echo esc_html__( 'Import CSV', 'lukic-code-snippets' ); ?>
									</button>
									<form id="import-meta-tags-form" style="display: none;">
										<input type="file" id="import-meta-tags-file" accept=".csv">
										<button type="submit" class="Lukic-btn">
											<?php echo esc_html__( 'Upload & Process', 'lukic-code-snippets' ); ?>
										</button>
									</form>
								</div>
							</div>
						</div>
						
						<div class="Lukic-card-body">
							<div class="Lukic-table-container">
								<table id="Lukic-meta-tags-table" class="Lukic-table display" width="100%">
									<thead>
										<tr>
											<th data-orderable="true"><?php echo esc_html__( 'Post/Term ID', 'lukic-code-snippets' ); ?></th>
											<th data-orderable="true"><?php echo esc_html__( 'URL', 'lukic-code-snippets' ); ?></th>
											<th data-orderable="true"><?php echo esc_html__( 'Meta Title', 'lukic-code-snippets' ); ?></th>
											<th data-orderable="true"><?php echo esc_html__( 'Meta Description', 'lukic-code-snippets' ); ?></th>
											<th data-orderable="false"><?php echo esc_html__( 'Actions', 'lukic-code-snippets' ); ?></th>
										</tr>
									</thead>
									<tbody>
										<?php foreach ( $all_urls as $item ) : ?>
											<tr data-id="<?php echo esc_attr( $item['id'] ); ?>" data-type="<?php echo esc_attr( $item['type'] ); ?>">
												<td><?php echo esc_html( $item['id'] ); ?></td>
												<td>
													<a href="<?php echo esc_url( $item['url'] ); ?>" target="_blank" class="Lukic-url-link">
														<?php echo esc_html( wp_parse_url( $item['url'], PHP_URL_PATH ) ); ?>
														<span class="dashicons dashicons-external"></span>
													</a>
												</td>
												<td>
													<div class="Lukic-editable" data-field="title">
														<span class="Lukic-text"><?php echo esc_html( $item['title'] ); ?></span>
														<input type="text" class="Lukic-input" value="<?php echo esc_attr( $item['title'] ); ?>" style="display: none;">
													</div>
												</td>
												<td>
													<div class="Lukic-editable" data-field="description">
														<span class="Lukic-text"><?php echo esc_html( $item['description'] ); ?></span>
														<textarea class="Lukic-input" style="display: none;"><?php echo esc_textarea( $item['description'] ); ?></textarea>
													</div>
												</td>
												<td>
													<button class="Lukic-btn Lukic-btn-small Lukic-edit-btn" data-id="<?php echo esc_attr( $item['id'] ); ?>" data-type="<?php echo esc_attr( $item['type'] ); ?>">
														<span class="dashicons dashicons-edit-large"></span> <?php echo esc_html__( 'Edit', 'lukic-code-snippets' ); ?>
													</button>
												</td>
											</tr>
										<?php endforeach; ?>
									</tbody>
								</table>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<?php
}

/**
 * AJAX callback to update meta tags
 */
function Lukic_update_meta_tag() {
	// Check nonce for security
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'Lukic_meta_tags_editor_nonce' ) ) {
		wp_send_json_error( array( 'message' => __( 'Security check failed', 'lukic-code-snippets' ) ) );
	}

	// Check if required data is set
	if ( ! isset( $_POST['id'] ) || ! isset( $_POST['field'] ) || ! isset( $_POST['value'] ) ) {
		wp_send_json_error( array( 'message' => __( 'Missing required data', 'lukic-code-snippets' ) ) );
	}

	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$id    = sanitize_text_field( wp_unslash( $_POST['id'] ) );
	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$field = sanitize_text_field( wp_unslash( $_POST['field'] ) );
	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$value = sanitize_textarea_field( wp_unslash( $_POST['value'] ) );
	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$type  = isset( $_POST['type'] ) ? sanitize_text_field( wp_unslash( $_POST['type'] ) ) : 'post';

	$success = false;
	$message = '';

	// Handle taxonomy IDs
	if ( $type === 'taxonomy' && strpos( $id, 'tax_' ) === 0 ) {
		$term_id = intval( str_replace( 'tax_', '', $id ) );

		if ( Lukic_is_yoast_active() ) {
			if ( $field === 'title' ) {
				// Get the taxonomy name for this term
				$term = get_term( $term_id );
				if ( ! is_wp_error( $term ) && $term ) {
					$taxonomy = $term->taxonomy;

					// Update Yoast SEO title for this taxonomy term
					return update_term_meta( $term_id, '_yoast_wpseo_title', $value );
				}
				return false;
			} elseif ( $field === 'description' ) {
				// Get the taxonomy name for this term
				$term = get_term( $term_id );
				if ( ! is_wp_error( $term ) && $term ) {
					$taxonomy = $term->taxonomy;

					// Update Yoast SEO description for this taxonomy term
					return update_term_meta( $term_id, '_yoast_wpseo_metadesc', $value );
				}
				return false;
			}
		} elseif ( Lukic_is_rank_math_active() ) {
			if ( $field === 'title' ) {
				return update_term_meta( $term_id, 'rank_math_title', $value );
			} elseif ( $field === 'description' ) {
				return update_term_meta( $term_id, 'rank_math_description', $value );
			}
		} else {
			// Store in custom meta if no SEO plugin is active
			if ( $field === 'title' ) {
				return update_term_meta( $term_id, '_meta_title', $value );
			} elseif ( $field === 'description' ) {
				return update_term_meta( $term_id, '_meta_description', $value );
			}
		}
	} else {
		// Handle posts, pages, CPTs
		$post_id = intval( $id );

		if ( Lukic_is_yoast_active() ) {
			if ( $field === 'title' ) {
				return update_post_meta( $post_id, '_yoast_wpseo_title', $value );
			} elseif ( $field === 'description' ) {
				return update_post_meta( $post_id, '_yoast_wpseo_metadesc', $value );
			}
		} elseif ( Lukic_is_rank_math_active() ) {
			if ( $field === 'title' ) {
				return update_post_meta( $post_id, 'rank_math_title', $value );
			} elseif ( $field === 'description' ) {
				return update_post_meta( $post_id, 'rank_math_description', $value );
			}
		} else {
			// Store in custom meta if no SEO plugin is active
			if ( $field === 'title' ) {
				return update_post_meta( $post_id, '_meta_title', $value );
			} elseif ( $field === 'description' ) {
				return update_post_meta( $post_id, '_meta_description', $value );
			}
		}
	}

	if ( $success ) {
		$message = __( 'Meta tag updated successfully', 'lukic-code-snippets' );
		wp_send_json_success( array( 'message' => $message ) );
	} else {
		$message = __( 'Failed to update meta tag', 'lukic-code-snippets' );
		wp_send_json_error( array( 'message' => $message ) );
	}
}
add_action( 'wp_ajax_Lukic_update_meta_tag', 'Lukic_update_meta_tag' );

/**
 * AJAX callback to export meta tags as CSV
 */
function Lukic_export_meta_tags_csv() {
	// Check nonce for security
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'Lukic_meta_tags_editor_nonce' ) ) {
		wp_send_json_error( array( 'message' => __( 'Security check failed', 'lukic-code-snippets' ) ) );
	}

	// Get all URLs
	$all_urls = Lukic_get_all_urls();

	// Prepare CSV data
	$csv_data   = array();
	$csv_data[] = array( 'ID', 'Type', 'URL', 'Meta Title', 'Meta Description' );

	foreach ( $all_urls as $item ) {
		$csv_data[] = array(
			$item['id'],
			$item['type'],
			$item['url'],
			$item['title'],
			$item['description'],
		);
	}

	// Generate CSV content
	$csv_content = '';
	foreach ( $csv_data as $row ) {
		// Escape fields that contain commas, quotes, or newlines
		foreach ( $row as &$field ) {
			if ( preg_match( '/[",\r\n]/', $field ) ) {
				$field = '"' . str_replace( '"', '""', $field ) . '"';
			}
		}
		$csv_content .= implode( ',', $row ) . "\n";
	}

	// Send CSV content
	wp_send_json_success(
		array(
			'filename' => 'meta-tags-export-' . gmdate( 'Y-m-d' ) . '.csv',
			'content'  => $csv_content,
		)
	);
}
add_action( 'wp_ajax_Lukic_export_meta_tags_csv', 'Lukic_export_meta_tags_csv' );

/**
 * AJAX callback to import meta tags from CSV
 */
function Lukic_import_meta_tags_csv() {
	// Check nonce for security
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'Lukic_meta_tags_editor_nonce' ) ) {
		wp_send_json_error( array( 'message' => __( 'Security check failed', 'lukic-code-snippets' ) ) );
	}

	// Check if file was uploaded
	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	if ( ! isset( $_FILES['file'] ) || $_FILES['file']['error'] !== UPLOAD_ERR_OK ) {
		wp_send_json_error( array( 'message' => __( 'No file uploaded or upload error', 'lukic-code-snippets' ) ) );
	}

	// Get file content
	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$file_content = file_get_contents( $_FILES['file']['tmp_name'] );
	if ( ! $file_content ) {
		wp_send_json_error( array( 'message' => __( 'Could not read file content', 'lukic-code-snippets' ) ) );
	}

	// Parse CSV
	$rows = array_map( 'str_getcsv', explode( "\n", $file_content ) );

	// Remove header row
	$header = array_shift( $rows );

	// Check if CSV format is valid
	if ( count( $header ) < 5 ||
		! in_array( 'ID', $header ) ||
		! in_array( 'Type', $header ) ||
		! in_array( 'Meta Title', $header ) ||
		! in_array( 'Meta Description', $header ) ) {
		wp_send_json_error( array( 'message' => __( 'Invalid CSV format. Please use the exported format as a template.', 'lukic-code-snippets' ) ) );
	}

	// Find column indexes
	$id_index    = array_search( 'ID', $header );
	$type_index  = array_search( 'Type', $header );
	$title_index = array_search( 'Meta Title', $header );
	$desc_index  = array_search( 'Meta Description', $header );

	// Process rows
	$updated_count = 0;
	$errors        = array();

	foreach ( $rows as $row ) {
		// Skip empty rows
		if ( empty( $row ) || count( $row ) <= 1 ) {
			continue;
		}

		// Skip rows with missing data
		if ( count( $row ) < 5 || empty( $row[ $id_index ] ) || empty( $row[ $type_index ] ) ) {
			continue;
		}

		$id          = sanitize_text_field( $row[ $id_index ] );
		$type        = sanitize_text_field( $row[ $type_index ] );
		$title       = isset( $row[ $title_index ] ) ? sanitize_text_field( $row[ $title_index ] ) : '';
		$description = isset( $row[ $desc_index ] ) ? sanitize_textarea_field( $row[ $desc_index ] ) : '';

		// Update meta title
		if ( ! empty( $title ) ) {
			$title_updated = Lukic_update_meta_value( $id, $type, 'title', $title );
			if ( $title_updated ) {
				++$updated_count;
			} else {
				/* translators: %s: Post/Page ID */
				$errors[] = sprintf( __( 'Failed to update title for ID: %s', 'lukic-code-snippets' ), $id );
			}
		}

		// Update meta description
		if ( ! empty( $description ) ) {
			$desc_updated = Lukic_update_meta_value( $id, $type, 'description', $description );
			if ( $desc_updated ) {
				++$updated_count;
			} else {
				/* translators: %s: Post/Page ID */
				$errors[] = sprintf( __( 'Failed to update description for ID: %s', 'lukic-code-snippets' ), $id );
			}
		}
	}

	// Send response
	if ( $updated_count > 0 ) {
		/* translators: %d: Number of meta values updated */
		$message = sprintf( __( '%d meta values updated successfully.', 'lukic-code-snippets' ), $updated_count );
		if ( ! empty( $errors ) ) {
			/* translators: %d: Number of errors that occurred */
			$message .= ' ' . sprintf( __( '%d errors occurred.', 'lukic-code-snippets' ), count( $errors ) );
		}
		wp_send_json_success(
			array(
				'message' => $message,
				'errors'  => $errors,
			)
		);
	} else {
		wp_send_json_error(
			array(
				'message' => __( 'No meta values were updated.', 'lukic-code-snippets' ),
				'errors'  => $errors,
			)
		);
	}
}
add_action( 'wp_ajax_Lukic_import_meta_tags_csv', 'Lukic_import_meta_tags_csv' );

/**
 * Helper function to update meta value
 */
function Lukic_update_meta_value( $id, $type, $field, $value ) {
	// Handle taxonomy IDs
	if ( $type === 'taxonomy' && strpos( $id, 'tax_' ) === 0 ) {
		$term_id = intval( str_replace( 'tax_', '', $id ) );

		if ( Lukic_is_yoast_active() ) {
			if ( $field === 'title' ) {
				// Get the taxonomy name for this term
				$term = get_term( $term_id );
				if ( ! is_wp_error( $term ) && $term ) {
					$taxonomy = $term->taxonomy;

					// Update Yoast SEO title for this taxonomy term
					return update_term_meta( $term_id, '_yoast_wpseo_title', $value );
				}
				return false;
			} elseif ( $field === 'description' ) {
				// Get the taxonomy name for this term
				$term = get_term( $term_id );
				if ( ! is_wp_error( $term ) && $term ) {
					$taxonomy = $term->taxonomy;

					// Update Yoast SEO description for this taxonomy term
					return update_term_meta( $term_id, '_yoast_wpseo_metadesc', $value );
				}
				return false;
			}
		} elseif ( Lukic_is_rank_math_active() ) {
			if ( $field === 'title' ) {
				return update_term_meta( $term_id, 'rank_math_title', $value );
			} elseif ( $field === 'description' ) {
				return update_term_meta( $term_id, 'rank_math_description', $value );
			}
		} else {
			// Store in custom meta if no SEO plugin is active
			if ( $field === 'title' ) {
				return update_term_meta( $term_id, '_meta_title', $value );
			} elseif ( $field === 'description' ) {
				return update_term_meta( $term_id, '_meta_description', $value );
			}
		}
	} else {
		// Handle posts, pages, CPTs
		$post_id = intval( $id );

		if ( Lukic_is_yoast_active() ) {
			if ( $field === 'title' ) {
				return update_post_meta( $post_id, '_yoast_wpseo_title', $value );
			} elseif ( $field === 'description' ) {
				return update_post_meta( $post_id, '_yoast_wpseo_metadesc', $value );
			}
		} elseif ( Lukic_is_rank_math_active() ) {
			if ( $field === 'title' ) {
				return update_post_meta( $post_id, 'rank_math_title', $value );
			} elseif ( $field === 'description' ) {
				return update_post_meta( $post_id, 'rank_math_description', $value );
			}
		} else {
			// Store in custom meta if no SEO plugin is active
			if ( $field === 'title' ) {
				return update_post_meta( $post_id, '_meta_title', $value );
			} elseif ( $field === 'description' ) {
				return update_post_meta( $post_id, '_meta_description', $value );
			}
		}
	}

	return false;
}
