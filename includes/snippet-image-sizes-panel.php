<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Snippet: Image Sizes Panel
 * Description: Displays available image sizes in the sidebar when viewing a single image in the WordPress admin dashboard
 */

/**
 * Add the image sizes panel to the attachment details sidebar
 */
function Lukic_add_image_sizes_panel() {
	// Only run on attachment edit screen
	$screen = get_current_screen();
	if ( ! $screen || $screen->base !== 'post' || $screen->post_type !== 'attachment' ) {
		return;
	}

	// Enqueue styles
	add_action( 'admin_head', 'Lukic_image_sizes_panel_styles' );

	// Add the panel to the sidebar
	add_action( 'add_meta_boxes', 'Lukic_add_image_sizes_metabox' );
}
add_action( 'current_screen', 'Lukic_add_image_sizes_panel' );

/**
 * Add a metabox for image sizes to the right sidebar
 */
function Lukic_add_image_sizes_metabox() {
	add_meta_box(
		'Lukic-image-sizes-metabox',
		__( 'Image Sizes', 'lukic-code-snippets' ),
		'Lukic_display_image_sizes_metabox',
		'attachment',
		'side',
		'high'
	);
}

/**
 * Display the image sizes metabox content
 *
 * @param WP_Post $post The attachment post object
 */
function Lukic_display_image_sizes_metabox( $post ) {
	// Only run for image attachments
	if ( ! wp_attachment_is_image( $post->ID ) ) {
		echo '<p>' . esc_html__( 'This is not an image attachment.', 'lukic-code-snippets' ) . '</p>';
		return;
	}

	// Get all registered image sizes
	$sizes   = get_intermediate_image_sizes();
	$sizes[] = 'full'; // Add the full size

	// Get the attachment metadata
	$metadata = wp_get_attachment_metadata( $post->ID );

	echo '<div class="Lukic-image-sizes-list">';

	// Loop through each size and add to the panel
	foreach ( $sizes as $size ) {
		// Get the image dimensions for this size
		if ( $size === 'full' ) {
			// Full size dimensions
			$width      = isset( $metadata['width'] ) ? $metadata['width'] : 0;
			$height     = isset( $metadata['height'] ) ? $metadata['height'] : 0;
			$dimensions = $width . ' × ' . $height;
			$label      = __( 'full', 'lukic-code-snippets' );
			$image_url  = wp_get_attachment_url( $post->ID );
		} else {
			// Get dimensions for the intermediate size
			if ( isset( $metadata['sizes'][ $size ] ) ) {
				$width      = $metadata['sizes'][ $size ]['width'];
				$height     = $metadata['sizes'][ $size ]['height'];
				$dimensions = $width . ' × ' . $height;
				$label      = $size;
				$image_url  = wp_get_attachment_image_url( $post->ID, $size );
			} else {
				// This size doesn't exist for this image
				continue;
			}
		}

		// Format the size name for display (convert underscores to spaces and capitalize words)
		$display_name = ucwords( str_replace( '_', ' ', $label ) );

		// Add this size to the panel
		echo '<div class="Lukic-image-size-item">';
		echo '<a href="' . esc_url( $image_url ) . '" class="Lukic-image-size-name" target="_blank">' . esc_html( $display_name ) . '</a>';
		echo '<span class="Lukic-image-size-dimensions">' . esc_html( $dimensions ) . '</span>';
		echo '</div>';
	}

	echo '</div>'; // Close .Lukic-image-sizes-list
}

/**
 * Add styles for the image sizes panel
 */
function Lukic_image_sizes_panel_styles() {
	?>
	<style type="text/css">
		/* Metabox styles */
		#Lukic-image-sizes-metabox .inside {
			padding: 0;
			margin: 0;
		}
		
		.Lukic-image-sizes-list {
			padding: 12px;
		}
		
		.Lukic-image-size-item {
			display: flex;
			justify-content: space-between;
			padding: 5px 0;
			border-bottom: 1px solid #f0f0f0;
		}
		
		.Lukic-image-size-item:last-child {
			border-bottom: none;
		}
		
		.Lukic-image-size-name {
			font-weight: 500;
			color: #0073aa;
			text-decoration: none;
			display: inline-block;
		}
		
		.Lukic-image-size-name:hover {
			color: #00a0d2;
			text-decoration: underline;
		}
		
		.Lukic-image-size-dimensions {
			color: #666;
			font-size: 12px;
		}
	</style>
	<?php
if ( ! defined( 'ABSPATH' ) ) exit;
}
