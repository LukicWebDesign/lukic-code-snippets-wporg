<?php
/**
 * Snippet: Image Attributes Editor
 * Description: Provides a table interface to edit image attributes (title, alt text, filename) and organize media
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add submenu for Image Attributes Editor
 */
function Lukic_image_attributes_editor_menu() {
	add_submenu_page(
		'lukic-code-snippets',
		__( 'Image Attributes Editor', 'lukic-code-snippets' ),
		__( 'Image Attributes Editor', 'lukic-code-snippets' ),
		'manage_options',
		'lukic-image-attributes-editor',
		'Lukic_image_attributes_editor_page'
	);
}
add_action( 'admin_menu', 'Lukic_image_attributes_editor_menu' );

/**
 * Localize data for the image attributes editor script when on the page.
 */
function Lukic_image_attributes_editor_localize( $hook ) {

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$current_page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
	if ( $current_page === 'lukic-image-attributes-editor' ) {
		wp_localize_script(
			'Lukic-image-attributes',
			'LukicImageEditor',
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'Lukic_image_editor_nonce' ),
			)
		);
	}
}
add_action( 'admin_enqueue_scripts', 'Lukic_image_attributes_editor_localize' );
/**
 * Render the Image Attributes Editor page
 */
function Lukic_image_attributes_editor_page() {

	// Include the header partial
	// Header component is already loaded in main plugin file

	// Get image count for stats
	$args        = array(
		'post_type'      => 'attachment',
		'post_mime_type' => 'image',
		'posts_per_page' => -1,
	);
	$images      = get_posts( $args );
	$image_count = count( $images );

	// Prepare stats for header
	$stats = array(
		array(
			'count' => $image_count,
			'label' => 'Images',
		),
	);

	// Display header with stats
	?>
	<div class="wrap Lukic-admin-page">
 
	
		
		<div class="Lukic-container">
		<?php Lukic_display_header( __( 'Image Attributes Editor', 'lukic-code-snippets' ), $stats ); ?>
			<div class="Lukic-content-wrapper">
				<div class="Lukic-content">
					<div class="Lukic-card">
						<div class="Lukic-card-header">
							<p class="Lukic-description"><?php echo esc_html__( 'Edit image attributes such as title, alt text, caption, and filename. Changes are saved automatically when you click outside of the edited field.', 'lukic-code-snippets' ); ?></p>
						</div>
						
						<div class="Lukic-card-body">
							<?php
							// Bulk Edit Panel (initially hidden)
							echo '<div id="Lukic-bulk-edit-panel" class="Lukic-bulk-edit-panel" style="display: none;">';
							echo '<h3>' . esc_html__( 'Bulk Edit', 'lukic-code-snippets' ) . ' <span id="selected-count">(0)</span> ' . esc_html__( 'items selected', 'lukic-code-snippets' ) . '</h3>';
							echo '<div class="bulk-edit-options">';

							// Alt Text
							echo '<div class="bulk-edit-field">';
							echo '<label for="bulk-alt-text">' . esc_html__( 'Alt Text', 'lukic-code-snippets' ) . '</label>';
							echo '<input type="text" id="bulk-alt-text" placeholder="' . esc_attr__( 'New alt text for selected images', 'lukic-code-snippets' ) . '">';
							echo '</div>';

							// Title
							echo '<div class="bulk-edit-field">';
							echo '<label for="bulk-title">' . esc_html__( 'Title', 'lukic-code-snippets' ) . '</label>';
							echo '<input type="text" id="bulk-title" placeholder="' . esc_attr__( 'New title for selected images', 'lukic-code-snippets' ) . '">';
							echo '</div>';

							// Caption
							echo '<div class="bulk-edit-field">';
							echo '<label for="bulk-caption">' . esc_html__( 'Caption', 'lukic-code-snippets' ) . '</label>';
							echo '<input type="text" id="bulk-caption" placeholder="' . esc_attr__( 'New caption for selected images', 'lukic-code-snippets' ) . '">';
							echo '</div>';

							// Actions
							echo '<div class="bulk-actions">';
							echo '<button id="apply-bulk-edit" class="Lukic-btn">' . esc_html__( 'Apply Changes', 'lukic-code-snippets' ) . '</button>';
							echo '<button id="clear-selection" class="Lukic-btn Lukic-btn-secondary">' . esc_html__( 'Clear Selection', 'lukic-code-snippets' ) . '</button>';
							echo '</div>';

							echo '</div>'; // .bulk-edit-options
							echo '</div>'; // .Lukic-bulk-edit-panel

							// Query for images
							$args   = array(
								'post_type'      => 'attachment',
								'post_mime_type' => 'image',
								'posts_per_page' => -1,
							);
							$images = get_posts( $args );

							if ( $images ) {
								echo '<table id="Lukic-image-attributes-table" class="Lukic-table display" width="100%">';
								echo '<thead>';
								echo '<tr>';
								echo '<th data-orderable="false" class="select-checkbox-column"><input type="checkbox" id="select-all-images"></th>';
								echo '<th data-orderable="false">' . esc_html__( 'Thumbnail', 'lukic-code-snippets' ) . '</th>';
								echo '<th data-orderable="true">' . esc_html__( 'Title', 'lukic-code-snippets' ) . '</th>';
								echo '<th data-orderable="true">' . esc_html__( 'Alt', 'lukic-code-snippets' ) . '</th>';
								echo '<th data-orderable="true">' . esc_html__( 'Caption', 'lukic-code-snippets' ) . '</th>';
								echo '<th data-orderable="true">' . esc_html__( 'File Name', 'lukic-code-snippets' ) . '</th>';
								echo '<th data-orderable="true">' . esc_html__( 'File Size', 'lukic-code-snippets' ) . '</th>';
								echo '<th>' . esc_html__( 'Edit', 'lukic-code-snippets' ) . '</th>';
								echo '<th>' . esc_html__( 'Delete', 'lukic-code-snippets' ) . '</th>';
								echo '</tr>';
								echo '</thead>';
								echo '<tbody>';

								foreach ( $images as $image ) {
									$title     = get_the_title( $image->ID );
									$alt       = get_post_meta( $image->ID, '_wp_attachment_image_alt', true );
									$caption   = wp_get_attachment_caption( $image->ID );
									$file_name = basename( get_attached_file( $image->ID ) );

									$file_path    = get_attached_file( $image->ID );
									$file_size_kb = filesize( $file_path );
									$file_size_mb = round( $file_size_kb / 1024 / 1024, 2 );

									echo '<tr>';
									echo '<td><input type="checkbox" class="image-select-checkbox" data-id="' . esc_attr( $image->ID ) . '"></td>';
									echo '<td><a href="' . esc_url( wp_get_attachment_image_src( $image->ID, 'full' )[0] ) . '" class="thumbnail-link" target="_blank">
                                    <img class="thumbnail-image" src="' . esc_url( wp_get_attachment_image_src( $image->ID, 'thumbnail' )[0] ) . '" alt="' . esc_attr( $title ) . '">
                                    </a></td>';

									echo '<td contenteditable="true" data-field="title" data-id="' . esc_attr( $image->ID ) . '">' . esc_html( $title ) . '</td>';
									echo '<td contenteditable="true" data-field="alt" data-id="' . esc_attr( $image->ID ) . '">' . esc_html( $alt ) . '</td>';
									echo '<td contenteditable="true" data-field="caption" data-id="' . esc_attr( $image->ID ) . '">' . esc_html( $caption ) . '</td>';
									echo '<td data-field="file_name" data-id="' . esc_attr( $image->ID ) . '">' . esc_html( $file_name ) . '</td>';
									echo '<td data-field="file_size" data-id="' . esc_attr( $image->ID ) . '">' . esc_html( $file_size_mb ) . ' MB</td>';
									echo '<td><button class="edit-image-btn" data-id="' . esc_attr( $image->ID ) . '">Edit</button></td>';
									echo '<td><button class="delete-image-btn" data-id="' . esc_attr( $image->ID ) . '">Delete</button></td>';
									echo '</tr>';
								}

								echo '</tbody>';
								echo '</table>';

								echo '<div class="Lukic-export-options">';
								echo '<a href="#" id="download-csv" class="Lukic-btn">' . esc_html__( 'Download CSV', 'lukic-code-snippets' ) . '</a>';
								echo '</div>';
							} else {
								echo '<p>' . esc_html__( 'No images found.', 'lukic-code-snippets' ) . '</p>';
							}

							echo '</div>'; // .Lukic-content
							echo '</div>'; // .Lukic-content-wrapper
							echo '</div>'; // .Lukic-container
							?>
					</div>
				</div>
			</div>
		</div>
	</div>
	<?php
}

/**
 * AJAX callback to update image attributes
 */
function Lukic_update_image_attribute() {
	check_ajax_referer( 'Lukic_image_editor_nonce', 'nonce' );

	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$field = isset( $_POST['field'] ) ? sanitize_text_field( wp_unslash( $_POST['field'] ) ) : '';
	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$id    = isset( $_POST['id'] ) ? absint( wp_unslash( $_POST['id'] ) ) : 0;
	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$value = isset( $_POST['value'] ) ? sanitize_text_field( wp_unslash( $_POST['value'] ) ) : '';

	if ( ! $id || ! $field ) {
		wp_send_json_error( array( 'message' => 'Missing required parameters' ) );
	}

	if ( ! in_array( $field, array( 'title', 'alt', 'caption', 'file_name' ), true ) ) {
		wp_send_json_error( array( 'message' => 'Invalid field type' ) );
	}

	$attachment = get_post( $id );
	if ( ! $attachment || 'attachment' !== $attachment->post_type || ! current_user_can( 'edit_post', $id ) ) {
		wp_send_json_error( array( 'message' => 'Permission denied' ) );
	}

	// Update the corresponding database record
	if ( $field === 'title' ) {
		$post_data = array(
			'ID'         => $id,
			'post_title' => $value,
		);
		wp_update_post( $post_data );
		wp_send_json_success( array( 'message' => 'Title updated successfully' ) );
	} elseif ( $field === 'alt' ) {
		update_post_meta( $id, '_wp_attachment_image_alt', $value );
		wp_send_json_success( array( 'message' => 'Alt text updated successfully' ) );
	} elseif ( $field === 'caption' ) {
		wp_update_post(
			array(
				'ID'           => $id,
				'post_excerpt' => $value,
			)
		);
		wp_send_json_success( array( 'message' => 'Caption updated successfully' ) );
	} elseif ( $field === 'file_name' ) {
		// Filename editing is disabled for safety reasons
		wp_send_json_error( array( 'message' => 'Filename editing is disabled' ) );
		/*
		// Update the file name
		$attachment    = get_post( $id );
		$file_path     = get_attached_file( $id );
		$new_file_path = str_replace( basename( $file_path ), $value, $file_path );

		// Prevent renaming with empty value
		if ( empty( $value ) ) {
			wp_send_json_error( array( 'message' => 'Filename cannot be empty' ) );
		}

		// Check if rename was successful
		if ( rename( $file_path, $new_file_path ) ) {
			update_attached_file( $id, $new_file_path );
			wp_send_json_success( array( 'message' => 'Filename updated successfully' ) );
		} else {
			wp_send_json_error( array( 'message' => 'Failed to update filename' ) );
		}
		*/
	}

	wp_send_json_error( array( 'message' => 'Invalid field type' ) );
}
add_action( 'wp_ajax_Lukic_update_image_attribute', 'Lukic_update_image_attribute' );

/**
 * AJAX callback to generate CSV file
 */
function Lukic_generate_csv_file() {
	check_ajax_referer( 'Lukic_image_editor_nonce', 'nonce' );

	if ( ! current_user_can( 'upload_files' ) ) {
		wp_die( 'Permission denied' );
	}

	$args   = array(
		'post_type'      => 'attachment',
		'post_mime_type' => 'image',
		'posts_per_page' => -1,
	);
	$images = get_posts( $args );

	$csv_data = "ID,Title,Alt,Caption,File Name,Image Link,File Size\n";

	foreach ( $images as $image ) {
		$id           = $image->ID;
		$title        = get_the_title( $id );
		$alt          = get_post_meta( $id, '_wp_attachment_image_alt', true );
		$caption      = wp_get_attachment_caption( $id );
		$file_name    = basename( get_attached_file( $id ) );
		$file_path    = get_attached_file( $id );
		$file_size_kb = filesize( $file_path );
		$file_size_mb = round( $file_size_kb / 1024 / 1024, 2 );
		$image_link   = wp_get_attachment_image_src( $id, 'full' )[0];

		// Escape CSV fields
		$title      = str_replace( '"', '""', $title );
		$alt        = str_replace( '"', '""', $alt );
		$caption    = str_replace( '"', '""', $caption );
		$file_name  = str_replace( '"', '""', $file_name );
		$image_link = str_replace( '"', '""', $image_link );

		$csv_data .= $id . ',"' . $title . '","' . $alt . '","' . $caption . '","' . $file_name . '","' . $image_link . '","' . $file_size_mb . '"' . "\n";
	}

	header( 'Content-Type: text/csv' );
	header( 'Content-Disposition: attachment; filename="image_data.csv"' );
	echo esc_html( $csv_data );
	wp_die();
}
add_action( 'wp_ajax_Lukic_generate_csv_file', 'Lukic_generate_csv_file' );

/**
 * AJAX callback to delete image
 */
function Lukic_delete_image() {
	check_ajax_referer( 'Lukic_image_editor_nonce', 'nonce' );

	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$id = isset( $_POST['id'] ) ? absint( wp_unslash( $_POST['id'] ) ) : 0;

	if ( ! $id ) {
		wp_send_json_error( array( 'message' => 'Missing image ID' ) );
	}

	$attachment = get_post( $id );
	if ( ! $attachment || 'attachment' !== $attachment->post_type || ! current_user_can( 'delete_post', $id ) ) {
		wp_send_json_error( array( 'message' => 'Permission denied' ) );
	}

	// Delete the attachment
	$result = wp_delete_attachment( $id, true );

	if ( $result ) {
		wp_send_json_success( array( 'message' => 'Image deleted successfully' ) );
	} else {
		wp_send_json_error( array( 'message' => 'Failed to delete image' ) );
	}
}
add_action( 'wp_ajax_Lukic_delete_image', 'Lukic_delete_image' );

/**
 * AJAX callback to bulk update image attributes
 */
function Lukic_bulk_update_images() {
	check_ajax_referer( 'Lukic_image_editor_nonce', 'nonce' );

	// Get parameters
	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$image_ids = isset( $_POST['image_ids'] ) ? array_values( array_filter( array_map( 'absint', wp_unslash( (array) $_POST['image_ids'] ) ) ) ) : array();
	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$title     = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$alt       = isset( $_POST['alt'] ) ? sanitize_text_field( wp_unslash( $_POST['alt'] ) ) : '';
	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$caption   = isset( $_POST['caption'] ) ? sanitize_text_field( wp_unslash( $_POST['caption'] ) ) : '';

	if ( empty( $image_ids ) ) {
		wp_send_json_error( array( 'message' => 'No images selected' ) );
	}

	$updated = array();
	$errors  = array();

	// Process each image
	foreach ( $image_ids as $id ) {
		$attachment = get_post( $id );
		if ( ! $attachment || 'attachment' !== $attachment->post_type || ! current_user_can( 'edit_post', $id ) ) {
			$errors[] = array(
				'id'      => $id,
				'field'   => 'permission',
				'message' => __( 'Permission denied for this image.', 'lukic-code-snippets' ),
			);
			continue;
		}

		// Update title if provided
		if ( ! empty( $title ) ) {
			$post_data = array(
				'ID'         => $id,
				'post_title' => $title,
			);
			$result    = wp_update_post( $post_data );
			if ( is_wp_error( $result ) ) {
				$errors[] = array(
					'id'      => $id,
					'field'   => 'title',
					'message' => $result->get_error_message(),
				);
			} else {
				$updated[] = array(
					'id'    => $id,
					'field' => 'title',
				);
			}
		}

		// Update alt text if provided
		if ( ! empty( $alt ) ) {
			update_post_meta( $id, '_wp_attachment_image_alt', $alt );
			$updated[] = array(
				'id'    => $id,
				'field' => 'alt',
			);
		}

		// Update caption if provided
		if ( ! empty( $caption ) ) {
			wp_update_post(
				array(
					'ID'           => $id,
					'post_excerpt' => $caption,
				)
			);
			$updated[] = array(
				'id'    => $id,
				'field' => 'caption',
			);
		}
	}

	wp_send_json_success(
		array(
			/* translators: %d: Number of images that were updated */
			'message' => sprintf( __( '%d images updated successfully', 'lukic-code-snippets' ), count( $updated ) ),
			'updated' => $updated,
			'errors'  => $errors,
		)
	);
}
add_action( 'wp_ajax_Lukic_bulk_update_images', 'Lukic_bulk_update_images' );
