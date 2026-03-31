<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Snippet: SVG Upload Support
 * Description: Enables SVG file uploads with sanitization for security
 */

if ( ! function_exists( 'Lukic_enable_svg_upload' ) ) {
	/**
	 * Enable SVG upload in WordPress
	 */
	function Lukic_enable_svg_upload() {
		// Add SVG to allowed mime types
		add_filter( 'upload_mimes', 'Lukic_add_svg_mime_type' );

		// Handle SVG upload in WordPress 4.7.1 and 4.7.2
		add_filter( 'wp_check_filetype_and_ext', 'Lukic_fix_svg_upload', 10, 4 );

		// Sanitize SVG on upload for security
		add_filter( 'wp_handle_upload_prefilter', 'Lukic_sanitize_svg' );

		// Fix SVG display in media library
		add_action( 'admin_head', 'Lukic_fix_svg_media_display' );

		// Fix featured image display for SVGs
		add_filter( 'wp_get_attachment_image_src', 'Lukic_fix_svg_size_attributes', 10, 4 );
		add_filter( 'wp_calculate_image_srcset', 'Lukic_disable_srcset_for_svg', 10, 1 );
	}
	Lukic_enable_svg_upload();

	/**
	 * Add SVG MIME type support
	 */
	function Lukic_add_svg_mime_type( $mimes ) {
		$mimes['svg']  = 'image/svg+xml';
		$mimes['svgz'] = 'image/svg+xml';
		return $mimes;
	}

	/**
	 * Fix SVG upload compatibility for WordPress 4.7.1 and 4.7.2
	 */
	function Lukic_fix_svg_upload( $data, $file, $filename, $mimes ) {
		global $wp_version;

		// WordPress 4.7.1 & 4.7.2 SVG upload issue workaround
		if ( version_compare( $wp_version, '4.7.1', '>=' ) && version_compare( $wp_version, '4.7.3', '<' ) ) {
			$filetype = wp_check_filetype( $filename, $mimes );

			if ( 'svg' === $filetype['ext'] ) {
				$data['ext']  = 'svg';
				$data['type'] = 'image/svg+xml';
			}
		}

		return $data;
	}

	/**
	 * Sanitize SVG for security
	 */
	function Lukic_sanitize_svg( $file ) {
		// Only process SVG files
		if ( $file['type'] === 'image/svg+xml' ) {
			// Read the uploaded file
			$file_content = file_get_contents( $file['tmp_name'] );

			// Basic security checks
			$disallowed = array(
				'script',               // No script tags
				'onclick',              // No onclick attributes
				'onload',               // No onload attributes
				'onunload',             // No onunload attributes
				'onabort',              // No onabort attributes
				'onerror',              // No onerror attributes
				'javascript',           // No javascript attributes
				'ajax',                 // No ajax calls
				'eval(',                // No eval functions
				'<html',                // No HTML tags
				'<body',                // No body tags
				'<iframe',              // No iframes
				'document.cookie',      // No cookie access
				'document.location',    // No location access
				'innerHTML',            // No innerHTML
				'fromCharCode',         // No fromCharCode
				'localStorage',         // No localStorage
				'sessionStorage',       // No sessionStorage
				'xlink:href="javascript', // No javascript in xlink:href
			);

			// Check for disallowed content
			foreach ( $disallowed as $term ) {
				if ( stripos( $file_content, $term ) !== false ) {
					$file['error'] = 'Security violation: The SVG file contains potentially malicious content.';
					return $file;
				}
			}
		}

		return $file;
	}

	/**
	 * Fix SVG display in Media Library
	 */
	function Lukic_fix_svg_media_display() {
		?>
		<style type="text/css">
			/* Make SVGs display properly in media library */
			.attachment-266x266, .thumbnail img {
				width: 100% !important;
				height: auto !important;
			}
			
			/* Ensure SVGs are properly sized in the featured image box */
			#postimagediv .inside img {
				width: 100% !important;
				height: auto !important;
			}
		</style>
		<?php
if ( ! defined( 'ABSPATH' ) ) exit;
	}

	/**
	 * Fix SVG images size attributes
	 */
	function Lukic_fix_svg_size_attributes( $image, $attachment_id, $size, $icon ) {
		if ( get_post_mime_type( $attachment_id ) === 'image/svg+xml' ) {
			if ( is_array( $image ) ) {
				// If SVG has no defined width/height, use reasonable defaults
				if ( $image[1] === 0 || $image[2] === 0 ) {
					$image[1] = 100;
					$image[2] = 100;
				}
			}
		}
		return $image;
	}

	/**
	 * Disable responsive image srcset for SVGs
	 */
	function Lukic_disable_srcset_for_svg( $sources ) {
		if ( isset( $sources[0]['url'] ) && pathinfo( $sources[0]['url'], PATHINFO_EXTENSION ) === 'svg' ) {
			return array();
		}
		return $sources;
	}
}
