<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Snippet: Admin Bar Site Visibility Indicator
 * Description: Shows whether search engines are discouraged from indexing the site
 */

if ( ! function_exists( 'Lukic_add_site_visibility_indicator' ) ) {
	/**
	 * Add site visibility indicator to the admin bar
	 */
	function Lukic_add_site_visibility_indicator() {
		global $wp_admin_bar;

		// Only show for users who can manage options
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Check if search engines are discouraged from indexing the site
		$blog_public = get_option( 'blog_public' );

		// Set text and class based on visibility status
		if ( $blog_public == 0 ) {
			$visibility_text = 'Site Visibility: OFF';
		} else {
			$visibility_text = 'Site Visibility: ON';
		}

		// Add the node to the admin bar
		$wp_admin_bar->add_node(
			array(
				'id'     => 'site-visibility-status',
				'parent' => 'top-secondary',
				'title'  => '<span class="ab-label">' . $visibility_text . '</span>',
				'href'   => admin_url( 'options-reading.php' ),
				'meta'   => array(
					'title' => 'Click to edit site visibility settings',
				),
			)
		);
	}
	add_action( 'wp_before_admin_bar_render', 'Lukic_add_site_visibility_indicator' );
}

if ( ! function_exists( 'Lukic_site_visibility_indicator_css' ) ) {
	/**
	 * Add custom CSS for the visibility indicator
	 */
	function Lukic_site_visibility_indicator_css() {
		// Check if search engines are discouraged from indexing the site
		$blog_public      = get_option( 'blog_public' );
		$background_color = ( $blog_public == 0 ) ? '#dc3232' : '#46b450';
		?>
		<style type="text/css">
			/* Target the actual link element inside the li */
			#wp-admin-bar-site-visibility-status > a.ab-item {
				background-color: <?php echo esc_attr( $background_color ); ?> !important;
				color: #fff !important;
				font-weight: bold !important;
			}

			#wp-admin-bar-site-visibility-status > a.ab-item span:hover {
				color: #fff !important;
			}
			
			/* Ensure our custom element appears in the right place */
			#wp-admin-bar-top-secondary {
				display: flex;
			}
			#wp-admin-bar-site-visibility-status {
				order: -1;
			}
		</style>
		<?php
if ( ! defined( 'ABSPATH' ) ) exit;
	}
	add_action( 'admin_head', 'Lukic_site_visibility_indicator_css' );
	add_action( 'wp_head', 'Lukic_site_visibility_indicator_css' );
}
