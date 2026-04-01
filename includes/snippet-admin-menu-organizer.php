<?php
/**
 * Snippet: Admin Menu Organizer
 * Description: Reorder, rename, hide, and reorganize admin menu items using a drag-and-drop interface.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'Lukic_admin_menu_organizer_init' ) ) {

	class Lukic_Admin_Menu_Organizer {

		/**
		 * Option name for storing settings
		 */
		private $option_name = 'lukic_admin_menu_settings';

		/**
		 * Read and sanitize stored menu settings.
		 *
		 * @return array
		 */
		private function get_sanitized_settings() {
			$settings = get_option( $this->option_name, array() );
			if ( ! is_array( $settings ) ) {
				return array();
			}

			$clean_settings = array();
			foreach ( $settings as $slug => $config ) {
				$slug = $this->sanitize_menu_slug( $slug );
				if ( '' === $slug || ! is_array( $config ) ) {
					continue;
				}

				$clean_settings[ $slug ] = array(
					'position' => isset( $config['position'] ) ? absint( $config['position'] ) : 0,
					'title'    => isset( $config['title'] ) ? sanitize_text_field( $config['title'] ) : '',
					'hidden'   => ! empty( $config['hidden'] ),
				);
			}

			return $clean_settings;
		}

		/**
		 * Sanitize a stored menu slug while preserving core query-string slugs.
		 *
		 * @param string $slug Raw slug.
		 * @return string
		 */
		private function sanitize_menu_slug( $slug ) {
			$slug = is_string( $slug ) ? sanitize_text_field( wp_unslash( $slug ) ) : '';
			return preg_replace( '/[^A-Za-z0-9_\-\.?=&\/]/', '', $slug );
		}

		/**
		 * Constructor
		 */
		public function __construct() {
			// Add submenu page
			add_action( 'admin_menu', array( $this, 'add_submenu_page' ), 100 );

			// Apply menu changes
			// We use a very late priority to ensure we capture all registered menus
			add_action( 'admin_menu', array( $this, 'apply_custom_menu' ), 9999 );
			
			// Add custom CSS for hidden items.
			add_action( 'admin_enqueue_scripts', array( $this, 'output_custom_css' ) );

			// AJAX handler for saving settings
			add_action( 'wp_ajax_lukic_save_menu_order', array( $this, 'save_settings' ) );

			// Enqueue scripts
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		}

		/**
		 * Add submenu page
		 */
		public function add_submenu_page() {
			add_submenu_page(
				'lukic-code-snippets',
				__( 'Admin Menu Organizer', 'lukic-code-snippets' ),
				__( 'Admin Menu Organizer', 'lukic-code-snippets' ),
				'manage_options',
				'lukic-admin-menu-organizer',
				array( $this, 'render_page' )
			);
		}

		/**
		 * Enqueue scripts and styles
		 */
		public function enqueue_scripts( $hook ) {
			// Only load on our specific page
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( isset( $_GET['page'] ) && sanitize_key( wp_unslash( $_GET['page'] ) ) === 'lukic-admin-menu-organizer' ) {
				wp_enqueue_script( 'jquery-ui-sortable' );
				
				wp_register_style( 'Lukic-admin-menu-styles', false, array(), Lukic_SNIPPET_CODES_VERSION );
				wp_enqueue_style( 'Lukic-admin-menu-styles' );
				wp_add_inline_style( 'Lukic-admin-menu-styles', '
					#lukic-menu-organizer-list { max-width: 800px; padding: 20px; }
					.lukic-menu-item { background: #fff; border: 1px solid #e5e5e5; margin-bottom: 10px; padding: 10px; display: flex; align-items: center; border-radius: 4px; }
					.lukic-menu-item:hover { border-color: var(--Lukic-primary); }
					.lukic-menu-item-handle { cursor: move; margin-right: 15px; color: #ccc; }
					.lukic-menu-item-handle:hover { color: #333; }
					.lukic-menu-item-content { flex-grow: 1; display: flex; justify-content: space-between; align-items: center; }
					.lukic-menu-item-title-group { display: flex; align-items: center; gap: 15px; flex-grow: 1; }
					.lukic-original-title { font-weight: 600; min-width: 150px; }
					.lukic-menu-title-input { width: 200px; }
					.lukic-menu-item-actions { margin-left: 20px; }
					.ui-sortable-placeholder { border: 1px dashed #ccc; visibility: visible !important; height: 50px; margin-bottom: 10px; background: #f9f9f9; }
				' );

				wp_localize_script( 'jquery-ui-sortable', 'LukicMenuOrganizer', array(
					'nonce'       => wp_create_nonce( 'lukic_menu_organizer_nonce' ),
					'saved'       => __( 'Settings saved! Reloading...', 'lukic-code-snippets' ),
					'errorSaving' => __( 'Error saving settings.', 'lukic-code-snippets' ),
					'networkErr'  => __( 'Network error.', 'lukic-code-snippets' ),
				) );

				wp_add_inline_script( 'jquery-ui-sortable', '
					jQuery(document).ready(function($) {
						$("#lukic-menu-organizer-list").sortable({
							handle: ".lukic-menu-item-handle",
							placeholder: "ui-sortable-placeholder",
							axis: "y"
						});

						$("#lukic-save-menu-order").on("click", function() {
							var $btn = $(this);
							var $spinner = $btn.siblings(".lukic-save-status").find(".spinner");
							var $msg = $("#lukic-save-message");

							$btn.prop("disabled", true);
							$spinner.addClass("is-active");
							$msg.text("");

							var items = {};
							$("#lukic-menu-organizer-list .lukic-menu-item").each(function(index) {
								var $el = $(this);
								var slug = $el.data("slug");
								items[slug] = {
									position: index,
									title: $el.find(".lukic-menu-title-input").val(),
									hidden: $el.find(".lukic-menu-hidden-checkbox").is(":checked") ? 1 : 0
								};
							});

							$.ajax({
								url: ajaxurl,
								type: "POST",
								data: {
									action: "lukic_save_menu_order",
									settings: items,
									nonce: LukicMenuOrganizer.nonce
								},
								success: function(response) {
									if (response.success) {
										$msg.css("color", "var(--Lukic-primary)").text(LukicMenuOrganizer.saved);
										setTimeout(function() {
											location.reload();
										}, 1000);
									} else {
										$msg.css("color", "red").text(LukicMenuOrganizer.errorSaving);
									}
								},
								error: function() {
									$msg.css("color", "red").text(LukicMenuOrganizer.networkErr);
								},
								complete: function() {
									$btn.prop("disabled", false);
									$spinner.removeClass("is-active");
									setTimeout(function() { $msg.fadeOut(); }, 3000);
								}
							});
						});
					});
				' );
			}
			
			// Add script for "Show All" toggle on all admin pages if needed
			$settings = $this->get_sanitized_settings();
			if ( ! empty( $settings ) ) {
				wp_enqueue_script( 'jquery' );
				wp_add_inline_script( 'jquery', '
					jQuery(document).ready(function($) {
						var $menu = $("#adminmenu");
						var $btn = $("<li class=\"wp-menu-separator\"><a href=\"#\" id=\"lukic-toggle-menus\" style=\"text-align: center; color: #fff; opacity: 0.7;\">Show All</a></li>");
						$btn.on("click", function(e) {
							e.preventDefault();
							$("body").toggleClass("show-all-menus");
							$(this).text( $("body").hasClass("show-all-menus") ? "Hide Hidden" : "Show All" );
						});
						$menu.append($btn);
					});
				' );
			}
		}

		/**
		 * Apply custom menu order and settings
		 */
		public function apply_custom_menu() {
			global $menu;

			if ( ! is_array( $menu ) ) {
				return;
			}

			$settings = $this->get_sanitized_settings();
			if ( empty( $settings ) ) {
				return;
			}

			// Create a map of current menu items by slug
			$current_menu_map = array();
			foreach ( $menu as $index => $item ) {
				if ( ! isset( $item[2] ) ) {
					continue;
				}
				$slug = $item[2];
				$current_menu_map[ $slug ] = $item;
				// Keep original index to preserve items not in settings
				$current_menu_map[ $slug ]['original_index'] = $index;
			}

			$new_menu = array();
			$used_slugs = array();

			// 1. Add items from settings in order
			foreach ( $settings as $slug => $config ) {
				if ( isset( $current_menu_map[ $slug ] ) ) {
					$item = $current_menu_map[ $slug ];
					
					// Rename if custom title is set
					// But do NOT rename separators
					if ( ! empty( $config['title'] ) && ( ! isset( $item[4] ) || strpos( $item[4], 'wp-menu-separator' ) === false ) ) {
						$item[0] = $config['title']; // Menu title is index 0
					}

					// Add to new menu
					// We need to assign a new index. WordPress uses float/int indices for order.
					// We'll just append for now and let WordPress handle the keys (it might need numeric keys).
					// Actually, $menu keys are important for order.
					
					$new_menu[] = $item;
					$used_slugs[ $slug ] = true;
				} elseif ( $slug === 'separator-custom' ) {
					// Handle custom separators if we implement them
					$new_menu[] = array( '', 'read', 'separator-custom-' . uniqid(), '', 'wp-menu-separator' );
				}
			}

			// 2. Add remaining items that weren't in settings
			foreach ( $menu as $index => $item ) {
				if ( ! isset( $item[2] ) ) {
					continue;
				}
				$slug = $item[2];
				if ( ! isset( $used_slugs[ $slug ] ) ) {
					$new_menu[] = $item;
				}
			}

			// 3. Re-index $menu with proper spacing to avoid conflicts
			// WordPress usually spaces them by 5 or 1.
			$final_menu = array();
			$index = 1;
			foreach ( $new_menu as $item ) {
				$final_menu[ $index ] = $item;
				$index++;
			}

			// Replace global menu
			$menu = $final_menu;
		}

		/**
		 * Output custom CSS to hide items
		 */
		public function output_custom_css() {
			$settings = $this->get_sanitized_settings();
			if ( empty( $settings ) ) {
				return;
			}

			$css = '';
			foreach ( $settings as $slug => $config ) {
				if ( ! empty( $config['hidden'] ) ) {
					// We need to find the ID. WordPress generates IDs based on slug usually.
					// Standard: menu-posts, menu-media, menu-pages, menu-comments
					// Plugins: toplevel_page_slug
					
					$id = $this->get_menu_id_from_slug( $slug );
					if ( $id ) {
						$css .= '#' . $id . " { display: none !important; }\n";
					}
				}
			}

			// Add class to show hidden items if toggled
			if ( ! empty( $css ) ) {
				wp_register_style( 'Lukic-admin-menu-visibility', false, array(), Lukic_SNIPPET_CODES_VERSION );
				wp_enqueue_style( 'Lukic-admin-menu-visibility' );
				wp_add_inline_style(
					'Lukic-admin-menu-visibility',
					'body:not(.show-all-menus) .wp-menu-separator { display: inherit; }' .
					$css .
					'body.show-all-menus li.menu-top { display: block !important; }'
				);
			}
		}

		/**
		 * Helper to guess menu ID from slug
		 */
		private function get_menu_id_from_slug( $slug ) {
			// Core menus
			$core_map = array(
				'index.php' => 'menu-dashboard',
				'edit.php' => 'menu-posts',
				'upload.php' => 'menu-media',
				'link-manager.php' => 'menu-links',
				'edit.php?post_type=page' => 'menu-pages',
				'edit-comments.php' => 'menu-comments',
				'themes.php' => 'menu-appearance',
				'plugins.php' => 'menu-plugins',
				'users.php' => 'menu-users',
				'tools.php' => 'menu-tools',
				'options-general.php' => 'menu-settings',
			);

			if ( isset( $core_map[ $slug ] ) ) {
				return $core_map[ $slug ];
			}

			// Custom post types: menu-posts-post_type
			if ( strpos( $slug, 'edit.php?post_type=' ) === 0 ) {
				$pt = str_replace( 'edit.php?post_type=', '', $slug );
				return sanitize_html_class( 'menu-posts-' . sanitize_key( $pt ) );
			}

			// Separators
			if ( strpos( $slug, 'separator' ) !== false ) {
				// Separators are tricky as they don't have stable IDs usually, just classes.
				// We might skip hiding separators by ID for now, or handle them differently.
				return '';
			}

			// Plugins: toplevel_page_slug
			return sanitize_html_class( 'toplevel_page_' . sanitize_title( $slug ) );
		}

			// Toggle button logic moved to enqueue_scripts

		/**
		 * Render the settings page
		 */
		public function render_page() {
			global $menu;

			// Get saved settings
			$settings = $this->get_sanitized_settings();

			// Prepare menu items for display
			// We want to merge current menu items with saved settings to ensure we show everything
			// but respect the saved order.
			
			$display_items = array();
			$used_slugs = array();

			// 1. Add saved items
			$sep_count = 1;
			if ( ! empty( $settings ) ) {
				foreach ( $settings as $slug => $config ) {
					// Find original title if possible
					$original_title = $config['title'];
					$is_separator = false;
					
					// Try to find in current global menu to get real current title/icon
					$found = false;
					foreach ( $menu as $m ) {
						if ( isset( $m[2] ) && $m[2] === $slug ) {
							$original_title = wp_strip_all_tags( $m[0] ); // Strip notification bubbles
							// Check if separator
							if ( isset( $m[4] ) && strpos( $m[4], 'wp-menu-separator' ) !== false ) {
								$is_separator = true;
							}
							$found = true;
							break;
						}
					}

					// If it's a separator, give it a name
					if ( $is_separator ) {
						/* translators: %d: separator number */
						$display_title = sprintf( __( 'Separator %d', 'lukic-code-snippets' ), $sep_count++ );
					} else {
						$display_title = $config['title'] ?: $original_title;
					}
					
					$display_items[] = array(
						'slug' => $slug,
						'title' => $display_title,
						'original_title' => $is_separator ? '' : $original_title,
						'hidden' => ! empty( $config['hidden'] ),
						'is_separator' => $is_separator,
					);
					$used_slugs[ $slug ] = true;
				}
			}

			// 2. Add remaining items from global menu
			foreach ( $menu as $item ) {
				if ( ! isset( $item[2] ) ) {
					continue;
				}
				$slug = $item[2];
				
				// Skip if already added
				if ( isset( $used_slugs[ $slug ] ) ) {
					continue;
				}

				$title = wp_strip_all_tags( $item[0] );
				$is_separator = ( isset( $item[4] ) && strpos( $item[4], 'wp-menu-separator' ) !== false );
				
				if ( $is_separator ) {
					/* translators: %d: separator number */
					$title = sprintf( __( 'Separator %d', 'lukic-code-snippets' ), $sep_count++ );
				}

				$display_items[] = array(
					'slug' => $slug,
					'title' => $title,
					'original_title' => $is_separator ? '' : $title,
					'hidden' => false,
					'is_separator' => $is_separator,
				);
			}

			?>
			<div class="wrap Lukic-settings-wrap">
				<?php if ( function_exists( 'Lukic_display_header' ) ) {
					Lukic_display_header( __( 'Admin Menu Organizer', 'lukic-code-snippets' ), array() );
				} else {
					echo '<h1>' . esc_html__( 'Admin Menu Organizer', 'lukic-code-snippets' ) . '</h1>';
				} ?>

				<div class="Lukic-settings-intro">
					<p><?php esc_html_e( 'Drag and drop menu items to the desired position. Optionally change menu item titles or hide some items until toggled by clicking "Show All" at the bottom of the admin menu.', 'lukic-code-snippets' ); ?></p>
				</div>

				<div class="Lukic-card">
					<div class="Lukic-card-body">
						<div id="lukic-menu-organizer-list">
							<?php foreach ( $display_items as $item ) : ?>
								<div class="lukic-menu-item" data-slug="<?php echo esc_attr( $item['slug'] ); ?>">
									<div class="lukic-menu-item-handle">
										<span class="dashicons dashicons-menu"></span>
									</div>
									<div class="lukic-menu-item-content">
										<div class="lukic-menu-item-title-group">
											<span class="lukic-original-title"><?php echo esc_html( $item['original_title'] ); ?></span>
											<input type="text" class="lukic-menu-title-input" value="<?php echo esc_attr( $item['title'] ); ?>" placeholder="<?php esc_attr_e( 'Rename...', 'lukic-code-snippets' ); ?>" <?php disabled( $item['is_separator'] ); ?>>
										</div>
										<div class="lukic-menu-item-actions">
											<label>
												<input type="checkbox" class="lukic-menu-hidden-checkbox" <?php checked( $item['hidden'] ); ?>>
												<?php esc_html_e( 'Hide until toggled', 'lukic-code-snippets' ); ?>
											</label>
										</div>
									</div>
								</div>
							<?php endforeach; ?>
						</div>
					</div>
					<div class="Lukic-submit-container">
						<div class="lukic-save-status" style="display: flex; align-items: center;">
							<span id="lukic-save-message" style="font-weight: bold; margin-right: 10px;"></span>
							<span class="spinner" style="float: none; margin: 0;"></span>
						</div>
						<button id="lukic-save-menu-order" class="button button-primary"><?php esc_html_e( 'Save Changes', 'lukic-code-snippets' ); ?></button>
					</div>
				</div>
			</div>
			<?php
		}

		/**
		 * AJAX handler to save settings
		 */
		public function save_settings() {
			check_ajax_referer( 'lukic_menu_organizer_nonce', 'nonce' );

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( 'Permission denied' );
			}

			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$settings = isset( $_POST['settings'] ) ? wp_unslash( (array) $_POST['settings'] ) : array();

			// Sanitize
			$clean_settings = array();
			if ( is_array( $settings ) ) {
				foreach ( $settings as $slug => $config ) {
					$slug = $this->sanitize_menu_slug( $slug );
					if ( '' === $slug || ! is_array( $config ) ) {
						continue;
					}

					$clean_settings[ $slug ] = array(
						'position' => isset( $config['position'] ) ? absint( $config['position'] ) : 0,
						'title'    => isset( $config['title'] ) ? sanitize_text_field( $config['title'] ) : '',
						'hidden'   => ! empty( $config['hidden'] ),
					);
				}
			}

			// Sort by position
			uasort( $clean_settings, function( $a, $b ) {
				return $a['position'] - $b['position'];
			} );

			update_option( $this->option_name, $clean_settings );

			wp_send_json_success();
		}

	}

	/**
	 * Initialize
	 */
	function Lukic_admin_menu_organizer_init() {
		new Lukic_Admin_Menu_Organizer();
	}
	add_action( 'init', 'Lukic_admin_menu_organizer_init' );
}
