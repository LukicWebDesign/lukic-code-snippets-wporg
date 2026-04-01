<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Snippet: Media Replacement
 * Description: Replace media files while maintaining the same ID, filename and publish date
 */

if ( ! function_exists( 'Lukic_media_replace_init' ) ) {
	/**
	 * Initialize the media replacement functionality
	 */
	function Lukic_media_replace_init() {
		// Add "Replace Media" link in media library
		add_filter( 'media_row_actions', 'Lukic_media_replace_row_action', 10, 2 );
		add_filter( 'attachment_fields_to_edit', 'Lukic_media_replace_attachment_fields', 10, 2 );

		// Add admin page for replacement form
		add_action( 'admin_menu', 'Lukic_media_replace_add_submenu' );

		// Handle form submission
		add_action( 'admin_init', 'Lukic_media_replace_handle_upload' );

		// Add success/error notices
		add_action( 'admin_notices', 'Lukic_media_replace_admin_notices' );

		// Enqueue inline styles
		add_action( 'admin_enqueue_scripts', 'Lukic_media_replace_enqueue_assets' );

		// Bust caches for replaced media URLs generated through WordPress APIs.
		add_filter( 'wp_get_attachment_url', 'Lukic_media_replace_version_attachment_url', 10, 2 );
		add_filter( 'wp_get_attachment_image_src', 'Lukic_media_replace_version_image_src', 10, 4 );
	}
	Lukic_media_replace_init();

	/**
	 * Enqueue inline styles
	 */
	function Lukic_media_replace_enqueue_assets( $hook ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['page'] ) || 'lukic-replace-media' !== sanitize_key( wp_unslash( $_GET['page'] ) ) ) {
			return;
		}

		wp_add_inline_style( 'Lukic-admin-styles', '
			.media-replace-container { display: flex; flex-wrap: wrap; gap: 30px; }
			.current-file-info, .replace-form { flex: 1; min-width: 300px; }
			.media-item { background: transparent; padding: 0; border: none; box-shadow: none; }
			.media-item h2, .replace-form h2 { margin-top: 0; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px solid #eee; color: #23282d; font-size: 1.3em; }
			.thumbnail-container { display: flex; gap: 20px; align-items: flex-start; }
			.file-info { flex: 1; }
			.file-info p { margin: 8px 0; line-height: 1.5; }
			.thumbnail { min-width: 150px; margin-right: 20px; padding: 5px; background: #fff; border: 1px solid #ddd; border-radius: 3px; box-shadow: 0 1px 2px rgba(0,0,0,0.05); }
			.thumbnail img { display: block; max-width: 100%; height: auto; }
			.replace-form { background: transparent; padding: 0; border: none; box-shadow: none; }
			.replace-form .description { margin-bottom: 20px; color: #666; }
			.replace-form p { margin: 15px 0; }
			.replace-form label { display: block; margin-bottom: 5px; }
			.replace-form input[type="file"] { padding: 10px 0; width: 100%; }
			.Lukic-switch-label { display: flex; align-items: center; }
			.Lukic-checkbox-text { margin-left: 8px; }
			.notice-warning { margin: 20px 0 0 0; }
			.Lukic-media-replace-no-id { margin: 0; }
			.Lukic-media-replace-no-id .content { background: transparent; border: none; box-shadow: none; max-width: 100%; text-align: left; }
			.Lukic-no-media-header { display: flex; align-items: center; margin-bottom: 20px; justify-content: flex-start; gap: 6px; }
			.Lukic-no-media-header .dashicons { font-size: 24px; width: 24px; height: 24px; color: var(--Lukic-primary); }
			.Lukic-no-media-header h2 { margin: 0 !important; color: #23282d !important; font-size: 24px !important; line-height: 24px !important; }
			.Lukic-media-replace-no-id ol { margin: 0 0 20px 20px; }
			.Lukic-media-replace-no-id ol li { margin-bottom: 8px; line-height: 1.5; }
			.Lukic-media-replace-no-id .button-primary { margin-top: 10px; background: var(--Lukic-primary) !important; border-color: var(--Lukic-primary) !important; color: #fff !important; text-shadow: none; padding: 5px 20px; }
			.Lukic-media-replace-no-id .button-primary:hover, .Lukic-media-replace-no-id .button-primary:focus { background: var(--Lukic-primary-hover) !important; border-color: var(--Lukic-primary-hover) !important; color: #fff !important; }
			.Lukic-submit-container .button-secondary { background: #fff; border-color: #ddd; color: #555; padding: 5px 20px; height: auto; line-height: 2; font-size: 14px; transition: all 0.2s; box-shadow: 0 1px 2px rgba(0,0,0,0.05); }
			.Lukic-submit-container .button-secondary:hover, .Lukic-submit-container .button-secondary:focus { border-color: gray; color: gray; background: #fff; box-shadow: 0 2px 4px rgba(0,0,0,0.1); transform: translateY(-1px); }
			@media screen and (max-width: 782px) { .media-item .thumbnail-container { flex-direction: column; } .thumbnail { margin-right: 0; margin-bottom: 15px; text-align: center; } .thumbnail img { margin: 0 auto; } }
		' );
	}

	/**
	 * Add replace action to media list table
	 *
	 * @param array  $actions
	 * @param object $post
	 * @return array
	 */
	function Lukic_media_replace_row_action( $actions, $post ) {
		if ( current_user_can( 'edit_post', $post->ID ) ) {
			$url                              = admin_url( 'upload.php?page=lukic-replace-media&attachment_id=' . $post->ID );
			$actions['Lukic_replace_media'] = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Replace Media', 'lukic-code-snippets' ) . '</a>';
		}
		return $actions;
	}

	/**
	 * Add replace link to attachment edit fields
	 *
	 * @param array  $form_fields
	 * @param object $post
	 * @return array
	 */
	function Lukic_media_replace_attachment_fields( $form_fields, $post ) {
		if ( current_user_can( 'edit_post', $post->ID ) ) {
			$link                                 = admin_url( 'upload.php?page=lukic-replace-media&attachment_id=' . $post->ID );
			$form_fields['Lukic_replace_media'] = array(
				'label' => '',
				'input' => 'html',
				'html'  => '<a href="' . esc_url( $link ) . '" class="button-secondary">' .
							esc_html__( 'Replace Media File', 'lukic-code-snippets' ) . '</a>',
			);
		}
		return $form_fields;
	}

	/**
	 * Add submenu page for replacement form
	 */
	function Lukic_media_replace_add_submenu() {
		add_submenu_page(
			'upload.php',                            // Parent slug
			__( 'Replace Media', 'lukic-code-snippets' ),  // Page title
			__( 'Replace Media', 'lukic-code-snippets' ),  // Menu title
			'upload_files',                          // Capability
			'lukic-replace-media',                 // Menu slug
			'Lukic_media_replace_page'             // Callback function
		);
	}

	/**
	 * Display the replacement form
	 */
	function Lukic_media_replace_page() {
		// Check for attachment ID
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$attachment_id = isset( $_GET['attachment_id'] ) ? intval( wp_unslash( $_GET['attachment_id'] ) ) : 0;

		// Include the header partial
		// Header component is already loaded in main plugin file

		// Output base HTML structure
		?>
		<div class="wrap Lukic-settings-wrap">
			<?php
			Lukic_display_header( __( 'Replace Media File', 'lukic-code-snippets' ), array() );
			?>
			

			
			<?php
			// Check if attachment ID is provided
			if ( ! $attachment_id ) {
				?>
				<div class="Lukic-settings-container">
					<div class="Lukic-media-replace-no-id">
						<div class="content">
							<div class="Lukic-no-media-header">
								<span class="dashicons dashicons-info"></span>
								<h2><?php esc_html_e( 'No Media Selected', 'lukic-code-snippets' ); ?></h2>
                               
						
							</div>

							<p><?php esc_html_e( 'You need to select a media file to replace. Here\'s how:', 'lukic-code-snippets' ); ?></p>
								<ol>
									<li>
										<?php
										printf(
											wp_kses(
												/* translators: %s: Media library link */
												__( 'Go to the %s', 'lukic-code-snippets' ),
												array()
											),
											wp_kses(
												sprintf(
													'<a href="%s">%s</a>',
													esc_url( admin_url( 'upload.php' ) ),
													esc_html__( 'Media Library', 'lukic-code-snippets' )
												),
												array(
													'a' => array(
														'href' => array(),
													),
												)
											)
										);
										?>
									</li>
									<li><?php esc_html_e( 'Find the file you want to replace', 'lukic-code-snippets' ); ?></li>
									<li><?php esc_html_e( 'Hover over the file and click "Replace Media"', 'lukic-code-snippets' ); ?></li>
								</ol>
								<p><?php esc_html_e( 'Alternatively, you can click on the file to view its attachment details, then click the "Replace Media" button.', 'lukic-code-snippets' ); ?></p>
								<p>
									<a href="<?php echo esc_url( admin_url( 'upload.php' ) ); ?>" class="button button-primary">
										<span class="dashicons dashicons-format-gallery" style="margin-top: 3px; margin-right: 5px;"></span>
										<?php esc_html_e( 'Go to Media Library', 'lukic-code-snippets' ); ?>
									</a>
								</p>
							
						</div>
					</div>
				</div>
				<?php
				return;
			}

		// Get attachment data
		$attachment = get_post( $attachment_id );
		if ( ! $attachment || 'attachment' !== $attachment->post_type ) {
			wp_die( esc_html__( 'Media file not found.', 'lukic-code-snippets' ) );
		}

		// Check permissions
		if ( ! current_user_can( 'edit_post', $attachment_id ) ) {
			wp_die( esc_html__( 'You do not have permission to edit this attachment.', 'lukic-code-snippets' ) );
		}

			// Get file details
			$filepath       = get_attached_file( $attachment_id );
			$filename       = basename( $filepath );
			$filetype       = wp_check_filetype( $filename );
			$filesize       = file_exists( $filepath ) ? size_format( filesize( $filepath ), 2 ) : __( 'File not found', 'lukic-code-snippets' );

			// Generate nonce for form
			$nonce = wp_create_nonce( 'Lukic-replace-media-' . $attachment_id );

			// Output form HTML
			?>
			<div class="Lukic-settings-intro">
				<p><?php esc_html_e( 'Replace your media file while maintaining the same ID, filename, and upload date.', 'lukic-code-snippets' ); ?></p>
			</div>
			
			<div class="Lukic-settings-container">
				<div class="media-replace-container">
					<div class="current-file-info">
						<div class="media-item">
							<h2><?php esc_html_e( 'Current File', 'lukic-code-snippets' ); ?></h2>
							
							<div class="thumbnail-container">
								<?php if ( wp_attachment_is_image( $attachment_id ) ) : ?>
									<div class="thumbnail">
										<?php echo wp_kses_post( wp_get_attachment_image( $attachment_id, array( 150, 150 ) ) ); ?>
									</div>
								<?php endif; ?>
								
								<div class="file-info">
									<p><strong><?php esc_html_e( 'Filename:', 'lukic-code-snippets' ); ?></strong> <?php echo esc_html( $filename ); ?></p>
									<p><strong><?php esc_html_e( 'File type:', 'lukic-code-snippets' ); ?></strong> <?php echo esc_html( $filetype['type'] ); ?></p>
									<p><strong><?php esc_html_e( 'File size:', 'lukic-code-snippets' ); ?></strong> <?php echo esc_html( $filesize ); ?></p>
									<p><strong><?php esc_html_e( 'Dimensions:', 'lukic-code-snippets' ); ?></strong> 
										<?php
										if ( wp_attachment_is_image( $attachment_id ) ) {
											$metadata = wp_get_attachment_metadata( $attachment_id );
											if ( isset( $metadata['width'] ) && isset( $metadata['height'] ) ) {
												echo esc_html( $metadata['width'] . ' × ' . $metadata['height'] . ' pixels' );
											} else {
												esc_html_e( 'Unknown', 'lukic-code-snippets' );
											}
										} else {
											esc_html_e( 'N/A', 'lukic-code-snippets' );
										}
										?>
									</p>
									<p><strong><?php esc_html_e( 'Uploaded on:', 'lukic-code-snippets' ); ?></strong> <?php echo esc_html( get_the_date( '', $attachment_id ) ); ?></p>
								</div>
							</div>
						</div>
					</div>
					
					<div class="replace-form">
						<h2><?php esc_html_e( 'Upload Replacement', 'lukic-code-snippets' ); ?></h2>
						
						<p class="description">
							<?php esc_html_e( 'Choose a new file to replace the current one. The new file will inherit the current file\'s ID, filename, and upload date.', 'lukic-code-snippets' ); ?>
						</p>
						
						<form method="post" enctype="multipart/form-data">
							<input type="hidden" name="action" value="Lukic_replace_media" />
							<input type="hidden" name="attachment_id" value="<?php echo esc_attr( $attachment_id ); ?>" />
							<input type="hidden" name="_wpnonce" value="<?php echo esc_attr( $nonce ); ?>" />
							
							<p>
								<label for="replacement_file">
									<strong><?php esc_html_e( 'Select New File', 'lukic-code-snippets' ); ?></strong>
								</label>
								<input type="file" name="replacement_file" id="replacement_file" required />
							</p>
							
							<p>
								<label class="Lukic-switch-label">
									<input type="checkbox" name="preserve_filename" value="1" checked="checked" />
									<span class="Lukic-checkbox-text"><?php esc_html_e( 'Preserve Original Filename', 'lukic-code-snippets' ); ?></span>
								</label>
							</p>
							
							<div class="Lukic-submit-container">
								<input type="submit" name="submit" id="submit" class="button button-primary" value="<?php esc_html_e( 'Replace File', 'lukic-code-snippets' ); ?>" />
								<a href="<?php echo esc_url( admin_url( 'upload.php' ) ); ?>" class="button button-secondary"><?php esc_html_e( 'Cancel', 'lukic-code-snippets' ); ?></a>
							</div>
						</form>
						
						<div class="notice notice-warning">
							<p>
								<?php
								echo wp_kses(
									__( '<strong>Warning:</strong> This operation cannot be undone. Make sure to backup your files before proceeding.', 'lukic-code-snippets' ),
									array(
										'strong' => array(),
									)
								);
								?>
							</p>
						</div>
					</div>
				</div>
			</div>
			
			<div class="Lukic-footer">
				<p>
				<?php
				echo esc_html( sprintf(
					/* translators: %s: Plugin version */
					__( 'Thank you for creating with WordPress | Lukic Snippet Codes v%s', 'lukic-code-snippets' ),
					Lukic_SNIPPET_CODES_VERSION
				) );
				?>
				</p>
			</div>
		</div>
		
		<?php
	}

	/**
	 * Handle the file replacement
	 */
	function Lukic_media_replace_handle_upload() {
		// Check if our form was submitted
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( ! isset( $_POST['action'] ) || sanitize_key( wp_unslash( $_POST['action'] ) ) !== 'lukic_replace_media' ) {
			return;
		}

		// Get attachment ID
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$attachment_id = isset( $_POST['attachment_id'] ) ? absint( wp_unslash( $_POST['attachment_id'] ) ) : 0;
		if ( ! $attachment_id ) {
			wp_die( esc_html__( 'No attachment ID specified.', 'lukic-code-snippets' ) );
		}

		$attachment = get_post( $attachment_id );
		if ( ! $attachment || 'attachment' !== $attachment->post_type ) {
			wp_die( esc_html__( 'Invalid attachment specified.', 'lukic-code-snippets' ) );
		}

		// Verify nonce
		check_admin_referer( 'Lukic-replace-media-' . $attachment_id );

		// Check permissions
		if ( ! current_user_can( 'edit_post', $attachment_id ) ) {
			wp_die( esc_html__( 'You do not have permission to edit this attachment.', 'lukic-code-snippets' ) );
		}

		// Check file upload
		if ( ! isset( $_FILES['replacement_file'] ) || ! is_array( $_FILES['replacement_file'] ) ) {
			Lukic_media_replace_set_error( __( 'No file was uploaded.', 'lukic-code-snippets' ) );
			wp_safe_redirect( admin_url( 'upload.php?page=lukic-replace-media&attachment_id=' . $attachment_id ) );
			exit;
		}

		// Check uploaded file
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
		$uploaded_file = $_FILES['replacement_file'];

		if ( empty( $uploaded_file['name'] ) || empty( $uploaded_file['tmp_name'] ) ) {
			Lukic_media_replace_set_error( __( 'No file was uploaded.', 'lukic-code-snippets' ) );
			wp_safe_redirect( admin_url( 'upload.php?page=lukic-replace-media&attachment_id=' . $attachment_id ) );
			exit;
		}

		if ( ! isset( $uploaded_file['error'] ) || (int) $uploaded_file['error'] !== UPLOAD_ERR_OK ) {
			Lukic_media_replace_set_error( __( 'The uploaded file could not be processed.', 'lukic-code-snippets' ) );
			wp_safe_redirect( admin_url( 'upload.php?page=lukic-replace-media&attachment_id=' . $attachment_id ) );
			exit;
		}

		if ( ! is_uploaded_file( $uploaded_file['tmp_name'] ) ) {
			Lukic_media_replace_set_error( __( 'Invalid upload source detected.', 'lukic-code-snippets' ) );
			wp_safe_redirect( admin_url( 'upload.php?page=lukic-replace-media&attachment_id=' . $attachment_id ) );
			exit;
		}

		// Get original file path
		$original_file_path = get_attached_file( $attachment_id );
		$original_filename  = basename( $original_file_path );
		$original_file_info = pathinfo( $original_file_path );
		$original_extension = isset( $original_file_info['extension'] ) ? strtolower( $original_file_info['extension'] ) : '';
		$original_filetype  = wp_check_filetype( $original_filename );
		$original_type      = isset( $original_filetype['type'] ) ? $original_filetype['type'] : '';

		// Get preserve filename setting
		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$preserve_filename = isset( $_POST['preserve_filename'] ) && '1' === sanitize_text_field( wp_unslash( $_POST['preserve_filename'] ) );

		$uploaded_file_check = wp_check_filetype_and_ext( $uploaded_file['tmp_name'], $uploaded_file['name'] );
		$uploaded_file_info  = pathinfo( $uploaded_file['name'] );
		$uploaded_extension  = ! empty( $uploaded_file_check['ext'] ) ? strtolower( $uploaded_file_check['ext'] ) : ( isset( $uploaded_file_info['extension'] ) ? strtolower( $uploaded_file_info['extension'] ) : '' );
		$uploaded_type       = ! empty( $uploaded_file_check['type'] ) ? $uploaded_file_check['type'] : '';

		if ( empty( $uploaded_extension ) || empty( $uploaded_type ) ) {
			Lukic_media_replace_set_error( __( 'The uploaded file type is not allowed.', 'lukic-code-snippets' ) );
			wp_safe_redirect( admin_url( 'upload.php?page=lukic-replace-media&attachment_id=' . $attachment_id ) );
			exit;
		}

		if ( ! empty( $original_extension ) && $original_extension !== $uploaded_extension ) {
			Lukic_media_replace_set_error(
				sprintf(
					/* translators: %1$s: New file extension, %2$s: Original file extension */
					__( 'The new file extension (%1$s) does not match the original extension (%2$s).', 'lukic-code-snippets' ),
					$uploaded_extension,
					$original_extension
				)
			);
			wp_safe_redirect( admin_url( 'upload.php?page=lukic-replace-media&attachment_id=' . $attachment_id ) );
			exit;
		}

		if ( ! empty( $original_type ) && $original_type !== $uploaded_type ) {
			Lukic_media_replace_set_error( __( 'The replacement file type must match the original media type.', 'lukic-code-snippets' ) );
			wp_safe_redirect( admin_url( 'upload.php?page=lukic-replace-media&attachment_id=' . $attachment_id ) );
			exit;
		}

		// Prepare new file name based on preservation setting
		if ( $preserve_filename && $original_extension ) {
			$new_filename = $original_filename;

		} else {
			// Use uploaded filename
			$new_filename = sanitize_file_name( $uploaded_file['name'] );
		}

		// Determine new path
		$new_file_path = $original_file_info['dirname'] . '/' . $new_filename;
		$temp_file_path = wp_tempnam( $new_filename );
		if ( ! $temp_file_path ) {
			Lukic_media_replace_set_error( __( 'Failed to prepare a safe temporary file for replacement.', 'lukic-code-snippets' ) );
			wp_safe_redirect( admin_url( 'upload.php?page=lukic-replace-media&attachment_id=' . $attachment_id ) );
			exit;
		}

		// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- File operation fallback for edge cases
		$temp_copy_result = @copy( $uploaded_file['tmp_name'], $temp_file_path );
		if ( ! $temp_copy_result ) {
			wp_delete_file( $temp_file_path );
			Lukic_media_replace_set_error( __( 'Failed to process the uploaded file. Check folder permissions.', 'lukic-code-snippets' ) );
			wp_safe_redirect( admin_url( 'upload.php?page=lukic-replace-media&attachment_id=' . $attachment_id ) );
			exit;
		}

		$backup_original_path = '';
		if ( $new_file_path === $original_file_path && @file_exists( $original_file_path ) ) {
			$backup_original_path = wp_tempnam( $original_filename );
			if ( ! $backup_original_path ) {
				wp_delete_file( $temp_file_path );
				Lukic_media_replace_set_error( __( 'Failed to prepare a backup of the original file.', 'lukic-code-snippets' ) );
				wp_safe_redirect( admin_url( 'upload.php?page=lukic-replace-media&attachment_id=' . $attachment_id ) );
				exit;
			}

			// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- File operation fallback for edge cases
			$backup_result = @copy( $original_file_path, $backup_original_path );
			if ( ! $backup_result ) {
				wp_delete_file( $temp_file_path );
				wp_delete_file( $backup_original_path );
				Lukic_media_replace_set_error( __( 'Failed to create a backup of the original media file.', 'lukic-code-snippets' ) );
				wp_safe_redirect( admin_url( 'upload.php?page=lukic-replace-media&attachment_id=' . $attachment_id ) );
				exit;
			}
		}

		// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- File operation fallback for edge cases
		$move_result = @copy( $temp_file_path, $new_file_path );
		wp_delete_file( $temp_file_path );

		if ( ! $move_result ) {
			if ( $backup_original_path && @file_exists( $backup_original_path ) ) {
				// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Restore best effort after failed replacement.
				@copy( $backup_original_path, $original_file_path );
				wp_delete_file( $backup_original_path );
			}
			Lukic_media_replace_set_error( __( 'Failed to move uploaded file. Check folder permissions.', 'lukic-code-snippets' ) );
			wp_safe_redirect( admin_url( 'upload.php?page=lukic-replace-media&attachment_id=' . $attachment_id ) );
			exit;
		}

		if ( $backup_original_path && @file_exists( $backup_original_path ) ) {
			wp_delete_file( $backup_original_path );
		}

		if ( $new_file_path !== $original_file_path && @file_exists( $original_file_path ) ) {
			wp_delete_file( $original_file_path );
		}

		$old_metadata = wp_get_attachment_metadata( $attachment_id );

		// Update attachment metadata
		require_once ABSPATH . 'wp-admin/includes/image.php';

		// Update attachment file path
		update_attached_file( $attachment_id, $new_file_path );

		// Remove previously generated image subsizes so WordPress cannot keep serving stale files.
		Lukic_media_replace_delete_generated_sizes( $old_metadata );

		// Generate and update attachment metadata
		$attachment_metadata = wp_generate_attachment_metadata( $attachment_id, $new_file_path );
		wp_update_attachment_metadata( $attachment_id, $attachment_metadata );
		update_post_meta( $attachment_id, '_Lukic_media_replace_version', time() );

		// Clean cache
		clean_attachment_cache( $attachment_id );
		clean_post_cache( $attachment_id );

		// Set success message
		Lukic_media_replace_set_success( __( 'Media file successfully replaced!', 'lukic-code-snippets' ) );

		// Redirect to media library
		wp_safe_redirect( admin_url( 'post.php?post=' . $attachment_id . '&action=edit' ) );
		exit;
	}

	/**
	 * Set error message in transient
	 */
	function Lukic_media_replace_set_error( $message ) {
		set_transient( 'Lukic_media_replace_error', $message, 60 );
	}

	/**
	 * Set success message in transient
	 */
	function Lukic_media_replace_set_success( $message ) {
		set_transient( 'Lukic_media_replace_success', $message, 60 );
	}

	/**
	 * Delete previously generated image subsizes before regenerating metadata.
	 *
	 * @param array $metadata Existing attachment metadata.
	 * @return void
	 */
	function Lukic_media_replace_delete_generated_sizes( $metadata ) {
		if ( empty( $metadata['file'] ) || empty( $metadata['sizes'] ) || ! is_array( $metadata['sizes'] ) ) {
			return;
		}

		$upload_dir = wp_get_upload_dir();
		$base_dir   = trailingslashit( path_join( $upload_dir['basedir'], dirname( $metadata['file'] ) ) );

		foreach ( $metadata['sizes'] as $size ) {
			if ( empty( $size['file'] ) ) {
				continue;
			}

			wp_delete_file( $base_dir . $size['file'] );
		}
	}

	/**
	 * Append a version query arg to replaced attachment URLs.
	 *
	 * @param string $url           Attachment URL.
	 * @param int    $attachment_id Attachment ID.
	 * @return string
	 */
	function Lukic_media_replace_version_attachment_url( $url, $attachment_id ) {
		$version = (int) get_post_meta( $attachment_id, '_Lukic_media_replace_version', true );
		if ( ! $version || empty( $url ) ) {
			return $url;
		}

		return esc_url_raw( add_query_arg( 'Lukic_v', $version, $url ) );
	}

	/**
	 * Append a version query arg to image URLs returned by WordPress image helpers.
	 *
	 * @param array|false $image         Image data.
	 * @param int         $attachment_id Attachment ID.
	 * @param mixed       $size          Requested size.
	 * @param bool        $icon          Whether the request is for an icon.
	 * @return array|false
	 */
	function Lukic_media_replace_version_image_src( $image, $attachment_id, $size, $icon ) {
		unset( $size, $icon );

		if ( ! is_array( $image ) || empty( $image[0] ) ) {
			return $image;
		}

		$version = (int) get_post_meta( $attachment_id, '_Lukic_media_replace_version', true );
		if ( ! $version ) {
			return $image;
		}

		$image[0] = esc_url_raw( add_query_arg( 'Lukic_v', $version, $image[0] ) );
		return $image;
	}

	/**
	 * Display admin notices for success/error messages
	 */
	function Lukic_media_replace_admin_notices() {
		// Check for error message
		$error = get_transient( 'Lukic_media_replace_error' );
		if ( $error ) {
			echo '<div class="notice notice-error is-dismissible"><p>' . esc_html( $error ) . '</p></div>';
			delete_transient( 'Lukic_media_replace_error' );
		}

		// Check for success message
		$success = get_transient( 'Lukic_media_replace_success' );
		if ( $success ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $success ) . '</p></div>';
			delete_transient( 'Lukic_media_replace_success' );
		}
	}
}
