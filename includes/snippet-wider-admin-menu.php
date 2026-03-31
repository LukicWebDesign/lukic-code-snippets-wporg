<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Snippet: Wider Admin Menu
 * Description: Makes the WordPress admin menu wider for better readability
 */

if ( ! function_exists( 'Lukic_wider_admin_menu' ) ) {
	/**
	 * Makes the WordPress admin menu wider
	 */
	function Lukic_wider_admin_menu() {
		// Set your desired menu width in pixels (default WordPress is 160px)
		$menu_width      = 200;
		$collapsed_width = 40; // Width of collapsed menu

		// Calculate content margins based on menu states
		$content_margin           = $menu_width + 20;
		$collapsed_content_margin = $collapsed_width + 20;

		// Output custom CSS
		?>
		<style type="text/css">
			/* Make admin menu wider */
			body:not(.folded) #adminmenuback, 
			body:not(.folded) #adminmenuwrap, 
			body:not(.folded) #adminmenu, 
			body:not(.folded) #adminmenu .wp-submenu {
				width: <?php echo esc_attr( $menu_width ); ?>px !important;
			}
			
			/* Adjust main content area */
			body:not(.folded) #wpcontent, 
			body:not(.folded) #wpfooter {
				margin-left: <?php echo esc_attr( $content_margin ); ?>px !important;
			}
			
			/* Fix submenu positioning */
			body:not(.folded) #adminmenu .wp-submenu {
				left: <?php echo esc_attr( $menu_width ); ?>px !important;
			}
			
			/* Fix submenu when open and active */
			body:not(.folded) #adminmenu .wp-has-current-submenu .wp-submenu {
				left: 0 !important;
				width: 100% !important;
				box-sizing: border-box;
			}
			
			/* Make sure submenu items stay within bounds */
			body:not(.folded) #adminmenu .wp-submenu a {
				padding-right: 10px;
				word-wrap: break-word;
				white-space: normal;
			}
			
			/* Fix active submenu item display */
			body:not(.folded) #adminmenu .wp-submenu .current a,
			body:not(.folded) #adminmenu .wp-submenu .current a:hover {
				width: auto;
				margin-right: 10px;
			}
			
			/* Fix Gutenberg editor width */
			body:not(.folded) .block-editor__container .components-navigate-regions {
				margin-left: -<?php echo esc_attr( $menu_width ); ?>px !important;
			}
			
			/* Collapsed state styles */
			body.folded #adminmenuback,
			body.folded #adminmenuwrap,
			body.folded #adminmenu {
				width: <?php echo esc_attr( $collapsed_width ); ?>px !important;
			}
			
			body.folded #wpcontent,
			body.folded #wpfooter {
				margin-left: <?php echo esc_attr( $collapsed_content_margin ); ?>px !important;
			}
			
			body.folded #adminmenu .wp-submenu {
				left: <?php echo esc_attr( $collapsed_width ); ?>px !important;
			}
		</style>
		<?php
if ( ! defined( 'ABSPATH' ) ) exit;
	}
	// Use a high priority (999) to ensure this runs after WordPress core styles
	add_action( 'admin_head', 'Lukic_wider_admin_menu', 999 );
}

// Add a debug function to verify the snippet is loaded
if ( ! function_exists( 'Lukic_wider_admin_menu_debug' ) ) {
	function Lukic_wider_admin_menu_debug() {
		// Only show for admins
		if ( current_user_can( 'manage_options' ) ) {
			echo '<!-- Lukic Wider Admin Menu snippet is active -->';
		}
	}
	add_action( 'admin_footer', 'Lukic_wider_admin_menu_debug' );
}
