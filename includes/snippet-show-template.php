<?php
/**
 * Show Current Template Snippet
 *
 * Displays the current template file name in the admin bar.
 * Very useful for theme development and debugging.
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Main class for the Show Current Template feature
 */
class Lukic_Show_Template {

	/**
	 * Template path storage
	 */
	private $template_path = '';

	/**
	 * Constructor
	 */
	public function __construct() {
		// Store the template being used
		add_filter( 'template_include', array( $this, 'store_template_path' ), 1000 );

		// Add admin bar node
		add_action( 'admin_bar_menu', array( $this, 'add_template_to_admin_bar' ), 1000 );

		// Add styles for the admin bar item
		add_action( 'wp_head', array( $this, 'add_custom_styles' ) );
		add_action( 'admin_head', array( $this, 'add_custom_styles' ) );
	}

	/**
	 * Store the path of the template being used
	 *
	 * @param string $template The template being used
	 * @return string The template being used
	 */
	public function store_template_path( $template ) {
		$this->template_path = $template;
		return $template;
	}

	/**
	 * Add current template info to the admin bar
	 *
	 * @param WP_Admin_Bar $admin_bar The admin bar object
	 */
	public function add_template_to_admin_bar( $admin_bar ) {
		if ( ! is_admin_bar_showing() || is_admin() ) {
			return;
		}

		// Get basic template info
		$template_name = basename( $this->template_path );
		$template_dir  = basename( dirname( $this->template_path ) );

		// Get theme info
		$theme = wp_get_theme();

		// Relative path from theme root
		$theme_root    = get_theme_root();
		$relative_path = str_replace( $theme_root, '', $this->template_path );
		$relative_path = ltrim( $relative_path, '/\\' );

		// Build template info string
		$template_info = $template_name;

		// Template hierarchy (for more detailed info)
		$templates = array();

		if ( is_embed() ) {
			$templates[] = 'Embed Template';
		} elseif ( is_404() ) {
			$templates[] = '404 Template';
		} elseif ( is_search() ) {
			$templates[] = 'Search Template';
		} elseif ( is_front_page() && is_home() ) {
			$templates[] = 'Front Page + Blog Template';
		} elseif ( is_front_page() ) {
			$templates[] = 'Front Page Template';
		} elseif ( is_home() ) {
			$templates[] = 'Blog Template';
		} elseif ( is_singular() ) {
			$post_type   = get_post_type();
			$templates[] = 'Singular Template';
			$templates[] = "Single Template: $post_type";

			if ( is_page() ) {
				$templates[] = 'Page Template';

				// Check for page templates
				$page_template = get_page_template_slug();
				if ( $page_template ) {
					$templates[] = "Page Template: $page_template";
				}
			}
		} elseif ( is_archive() ) {
			$templates[] = 'Archive Template';

			if ( is_post_type_archive() ) {
				$post_type   = get_post_type();
				$templates[] = "Archive Template: $post_type";
			} elseif ( is_tax() || is_category() || is_tag() ) {
				$term        = get_queried_object();
				$taxonomy    = $term->taxonomy;
				$templates[] = "Taxonomy Template: $taxonomy";
			} elseif ( is_author() ) {
				$templates[] = 'Author Template';
			} elseif ( is_date() ) {
				$templates[] = 'Date Template';
			}
		}

		// Build tooltip content
		$tooltip  = '<strong>Template File:</strong> ' . $template_name;
		$tooltip .= '<br><strong>Template Path:</strong> ' . $relative_path;
		$tooltip .= '<br><strong>Theme:</strong> ' . $theme->get( 'Name' ) . ' (' . $theme->get( 'Version' ) . ')';

		if ( ! empty( $templates ) ) {
			$tooltip .= '<br><strong>Template Hierarchy:</strong><br> - ' . implode( '<br> - ', $templates );
		}

		// Add node to admin bar
		$admin_bar->add_node(
			array(
				'id'    => 'Lukic-current-template',
				'title' => '<span class="Lukic-template-label">Template:</span> <span class="Lukic-template-info">' . esc_html( $template_info ) . '</span>',
				'href'  => '#',
				'meta'  => array(
					'title' => $tooltip,
					'class' => 'Lukic-template-node',
				),
			)
		);
	}

	/**
	 * Add custom styles for the admin bar template info
	 */
	public function add_custom_styles() {
		if ( ! is_admin_bar_showing() ) {
			return;
		}

		?>
		<style type="text/css">
			#wp-admin-bar-Lukic-current-template {
				background-color: rgba(0, 0, 0, 0.15) !important;
			}
			#wp-admin-bar-Lukic-current-template .Lukic-template-label {
				font-weight: 500;
				color: #eee;
			}
			#wp-admin-bar-Lukic-current-template .Lukic-template-info {
				font-family: Consolas, Monaco, monospace;
				font-size: 12px;
				padding: 0 5px;
				color: #fff;
			}
			#wp-admin-bar-Lukic-current-template:hover .Lukic-template-info {
				color: #00E1AF;
			}
			#wpadminbar .Lukic-template-node .ab-item:hover {
				background-color: #32373c !important;
				color: #00E1AF !important;
			}
			#wpadminbar .Lukic-template-node .ab-item:hover .Lukic-template-label {
				color: #eee;
			}
		</style>
		<?php
	}
}

/**
 * Initialize the Show Template feature
 */
function Lukic_show_template_init() {
	new Lukic_Show_Template();
}

// Initialize the feature when this snippet is included
Lukic_show_template_init();
