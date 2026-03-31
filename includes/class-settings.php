<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Settings class for Lukic Snippet Codes plugin
 */
class Lukic_Snippet_Codes_Settings {

	/**
	 * Constructor
	 */
	public function __construct() {
		// Add main menu and main submenu item
		add_action( 'admin_menu', array( $this, 'add_main_menu' ) );

		// Add settings submenu page (with high priority to appear last)
		add_action( 'admin_menu', array( $this, 'add_settings_submenu' ), 999 );

		// Register settings
		add_action( 'admin_init', array( $this, 'register_settings' ) );

		// AJAX handlers for auto-save
		add_action( 'wp_ajax_Lukic_auto_save_snippet', array( $this, 'ajax_auto_save_snippet' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_auto_save_script' ) );
		add_action( 'admin_head', array( $this, 'add_menu_icon_styles' ) );
	}

	/**
	 * Enqueue admin scripts and styles
	 */
	public function enqueue_admin_scripts( $hook ) {
		// Update the hook check for the new menu page
		if ( 'toplevel_page_lukic-code-snippets' !== $hook ) {
			return;
		}

		// Enqueue jQuery UI components
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'jquery-ui-core' );
		wp_enqueue_script( 'jquery-ui-tabs' );
		wp_enqueue_script( 'jquery-ui-sortable' );
		wp_enqueue_script( 'jquery-ui-dialog' );

		// Enqueue jQuery UI theme for styling
		wp_enqueue_style( 'wp-jquery-ui-dialog' );

		// CSS is now handled by the main plugin centrally
		
		wp_register_style( 'Lukic-admin-styles', false );
		wp_enqueue_style( 'Lukic-admin-styles' );
		wp_add_inline_style( 'Lukic-admin-styles', '
			@keyframes lukic-spin { 
				100% { transform: rotate(360deg); } 
			}
			.spinning-icon { 
				animation: lukic-spin 1s linear infinite; 
			}
		' );

		wp_add_inline_script( 'jquery', '
			jQuery(document).ready(function($) {
				
				// Filter snippets
				function filterSnippets() {
					// Get the search term from the input (add trim to remove whitespace)
					const searchTerm = $("#snippet-search").val().toLowerCase().trim();
					// Get selected tag from active tag button
					const selectedTag = $(".Lukic-tag-button.active").data("tag") || "";
					// Get filter type (if any)
					const filterType = $(".Lukic-tag-button.active").data("filter-type") || "";
					
					// Always reset no results message
					$(".Lukic-no-search-results").remove();
					
					// Initialize counter for visible snippets
					let visibleSnippetsCount = 0;
					
					// If no filters active (no search term and showing all tags), show everything
					if ((selectedTag === "" || selectedTag === "all") && searchTerm === "") {
						// Show all snippets when no filters are active
						$(".Lukic-snippet").show();
						$(".Lukic-category").show();
						return; // Exit early, no need to check each snippet
					}
					
					// Apply filters to each snippet
					$(".Lukic-snippet").each(function() {
						const $snippet = $(this);
						const snippetName = $snippet.find("h3").text().toLowerCase();
						const snippetDesc = $snippet.find(".Lukic-snippet-description p").text().toLowerCase();
						
						// Get all tag elements and extract their text content
						const snippetTagElements = $snippet.find(".Lukic-tag");
						const snippetTagsArray = [];
						snippetTagElements.each(function() {
							snippetTagsArray.push($(this).text().toLowerCase());
						});
						
						// Get the active status
						const isActive = $snippet.attr("data-active") === "true";
						
						// Check if search term matches
						const matchesSearch = searchTerm === "" || 
							snippetName.includes(searchTerm) || 
							snippetDesc.includes(searchTerm) || 
							snippetTagsArray.some(tag => tag.includes(searchTerm));
						
						// Handle different filter types
						let matchesFilter = true;
						
						if (filterType === "status" && selectedTag === "active") {
							// Filter by active status
							matchesFilter = isActive;
						} else if (selectedTag && selectedTag !== "" && selectedTag !== "all") {
							// Regular tag filtering - only if a specific tag is selected
							matchesFilter = false; // Default to false when filtering by tag
							
							// Convert selected tag to lowercase for case-insensitive comparison
							const lowerSelectedTag = selectedTag.toLowerCase();
							
							// Check each tag in the snippet
							for (let i = 0; i < snippetTagsArray.length; i++) {
								// Trim whitespace and convert to lowercase
								const snippetTag = snippetTagsArray[i].trim().toLowerCase();
								if (snippetTag === lowerSelectedTag) {
									matchesFilter = true;
									break;
								}
							}
						}
						
						// Show snippet only if BOTH conditions are met
						if (matchesSearch && matchesFilter) {
							$snippet.show();
							visibleSnippetsCount++;
						} else {
							$snippet.hide();
						}
					});
					
					// Show/hide category headers based on visible snippets
					$(".Lukic-category").each(function() {
						const visibleSnippets = $(this).find(".Lukic-snippet:visible").length;
						
						if (visibleSnippets > 0) {
							$(this).show();
						} else {
							$(this).hide();
						}
					});
					
					// Show no results message if needed
					const anyVisibleSnippets = $(".Lukic-snippet:visible").length > 0;
					if (!anyVisibleSnippets) {
						$(".Lukic-snippets-container").append(
							"<div class=\"Lukic-no-search-results\">" +
							"<p>" + "No snippets found matching your criteria. Try a different search term or tag filter." + "</p>" +
							"<p><strong>Tip:</strong> Click the \"All\" tag button to see all snippets.</p>" +
							"</div>"
						);
					}
				}
				
				// Apply filters when search input changes
				$("#snippet-search").on("input", function() {
					filterSnippets();
				});
				
				// Make sure the search input is focused when clicked
				$("#snippet-search").on("click", function() {
					$(this).focus();
				});
				
				// Dropdown filter removed - now using only tag buttons
				
				// Handle tag button clicks
				$(document).on("click", ".Lukic-tag-button", function() {
					// First, show all snippets to reset any previous filtering
					$(".Lukic-snippet").show();
					$(".Lukic-category").show();
					$(".Lukic-no-search-results").remove();
					
					// Get tag data
					const tag = $(this).data("tag");
					const filterType = $(this).data("filter-type") || "";
					
					// Update active state
					$(".Lukic-tag-button").removeClass("active");
					$(this).addClass("active");
					
					// Clear search input when changing tags to avoid confusion
					if ($("#snippet-search").val()) {
						$("#snippet-search").val("");
					}
					
					// Filter snippets with a slight delay to ensure DOM is updated
					setTimeout(function() {
						filterSnippets();
					}, 50);
				});
				
				// Make tags in snippet boxes clickable for filtering
				$(document).on("click", ".Lukic-tag", function(e) {
					e.preventDefault();
					const tag = $(this).text().toLowerCase();
					
					// Find and click corresponding tag button if exists
					const $tagButton = $(".Lukic-tag-button").filter(function() {
						return $(this).text().toLowerCase() === tag;
					});
					
					if ($tagButton.length) {
						$tagButton.click();
					} else {
						// If no matching button exists, create a temporary one and click it
						// This handles cases where a snippet has a tag that isn\'t in the top tags list
						$(".Lukic-tag-button").removeClass("active");
						
						// Use active tag styling to indicate selection
						filterSnippets();
					}
					
					// Filter snippets
					filterSnippets();
				});
				
				// Set the initial active button to "All"
				$(".Lukic-tag-button[data-tag=\"\"]").addClass("active");
				
				// Update data-active attribute when checkboxes are toggled
				$(document).on("change", ".Lukic-snippet input[type=\"checkbox\"]", function() {
					const isChecked = $(this).is(":checked");
					$(this).closest(".Lukic-snippet").attr("data-active", isChecked ? "true" : "false");
					
					// If the active filter is currently selected, re-filter to update the view
					if ($(".Lukic-tag-button.active").data("tag") === "active") {
						filterSnippets();
					}
				});
				
				// Handle Toggle All button
				$("#lukic-toggle-all-snippets").on("click", function(e) {
					e.preventDefault();
					const $btn = $(this);
					const $checkboxes = $(".Lukic-snippet:visible input[type=\"checkbox\"]");
					
					if (!$checkboxes.length) return;
					
					// Determine target state: if all visible are checked, turn them off. Otherwise turn all on.
					const allChecked = $checkboxes.length === $checkboxes.filter(":checked").length;
					const newState = !allChecked;
					
					// Visual updates
					$btn.find("span").last().text("' . esc_js( __( 'Saving...', 'lukic-code-snippets' ) ) . '");
					$btn.find(".dashicons").addClass("spinning-icon"); // Will add CSS below
					$btn.prop("disabled", true);
					
					// Change checkboxes without triggering "change" event to avoid auto-save storm
					$checkboxes.prop("checked", newState);
					
					// Update snippets visual state immediately
					$checkboxes.each(function() {
						const $snippetBox = $(this).closest(".Lukic-snippet");
						$snippetBox.attr("data-active", newState ? "true" : "false");
						if (newState) {
							$snippetBox.addClass("snippet-active");
						} else {
							$snippetBox.removeClass("snippet-active");
						}
					});
					
					// Gather all snippet options on page
					const currentOptions = {};
					$("input[name^=\"Lukic_snippet_codes_options[\"]").each(function () {
						const name = $(this).attr("name");
						const match = name.match(/Lukic_snippet_codes_options\[(.+?)\]/);
						if (match) {
							currentOptions[match[1]] = $(this).is(":checked") ? "1" : "0";
						}
					});
					
					// Single bulk AJAX save
					$.ajax({
						url: ajaxurl,
						type: "POST",
						data: {
							action: "Lukic_auto_save_snippet",
							nonce: Lukic_auto_save.nonce,
							options: JSON.stringify(currentOptions)
						},
						success: function(response) {
							// Reload to apply any snippet hooks that need refreshing
							window.location.reload();
						},
						error: function() {
							alert("' . esc_js( __( 'Error saving settings.', 'lukic-code-snippets' ) ) . '");
							window.location.reload();
						}
					});
				});

				// Trigger initial filtering on page load
				filterSnippets();
			});
		' );
	}

	/**
	 * Add main menu and main submenu page
	 */
	public function add_main_menu() {

		$icon_url = plugin_dir_url( __DIR__ ) . 'assets/icons/plugin-icon.svg';
		// Use add_menu_page instead of add_options_page to create a top-level menu
		add_menu_page(
			__( 'Lukic Code Snippets', 'lukic-code-snippets' ),
			__( 'Code Snippets', 'lukic-code-snippets' ),
			'manage_options',
			'lukic-code-snippets',
			array( $this, 'display_snippets_page' ),
			$icon_url,
			80 // Position in the menu
		);
	}

	/**
	 * Add custom menu icon styles
	 */
	public function add_menu_icon_styles() {

		$icon_url = plugin_dir_url( __DIR__ ) . 'assets/icons/plugin-icon.svg';
		?>
		<style>
			#adminmenu .toplevel_page_lukic-code-snippets .wp-menu-image {
				background-repeat: no-repeat;
				background-position: center center;
				background-size: 20px auto;
				background-image: url('<?php echo esc_url( $icon_url ); ?>');
				filter: invert(67%) sepia(72%) saturate(459%) hue-rotate(121deg) brightness(97%) contrast(101%);
			}
			#adminmenu .toplevel_page_lukic-code-snippets .wp-menu-image img {
				display: none;
			}
			#adminmenu .toplevel_page_lukic-code-snippets .wp-menu-image:before {
				content: '';
			}
		</style>
		<?php
if ( ! defined( 'ABSPATH' ) ) exit;
	}

	/**
	 * Add settings submenu page (appears last in submenu with priority 999)
	 */
	public function add_settings_submenu() {
		// Add submenu page for plugin settings
		add_submenu_page(
			'lukic-code-snippets',
			__( 'Plugin Settings', 'lukic-code-snippets' ),
			__( 'Settings', 'lukic-code-snippets' ),
			'manage_options',
			'lukic-code-snippets-settings',
			array( $this, 'display_plugin_settings_page' )
		);

		// Add submenu page for documentation
		add_submenu_page(
			'lukic-code-snippets',
			__( 'Documentation', 'lukic-code-snippets' ),
			__( 'Documentation', 'lukic-code-snippets' ),
			'manage_options',
			'lukic-code-snippets-documentation',
			'Lukic_display_documentation_page'
		);
	}

	/**
	 * Register plugin settings
	 */
	public function register_settings() {
		register_setting(
			'Lukic_code_snippets',
			'Lukic_snippet_codes_options',
			array( $this, 'sanitize_settings' )
		);

		register_setting(
			'Lukic_code_snippets_settings',
			'Lukic_snippet_codes_cleanup',
			array( $this, 'sanitize_cleanup' )
		);
	}

	/**
	 * Sanitize settings
	 */
	public function sanitize_settings( $input ) {
		$sanitized_input = array();
		foreach ( Lukic_Snippet_Registry::get_snippets() as $snippet_id => $snippet ) {
			$sanitized_input[ $snippet_id ] = ( isset( $input[ $snippet_id ] ) && (int) $input[ $snippet_id ] === 1 ) ? 1 : 0;
		}

		return $sanitized_input;
	}

	/**
	 * Sanitize cleanup settings
	 */
	public function sanitize_cleanup( $input ) {
		$valid_values = array( 'preserve', 'delete' );

		if ( in_array( $input, $valid_values ) ) {
			return $input;
		}

		return 'preserve';
	}

	/**
	 * Display snippets page
	 */
	public function display_snippets_page() {
		// Check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$categories           = Lukic_Snippet_Registry::get_categories();
		$snippets_by_category = Lukic_Snippet_Registry::get_snippets_by_category();

		// Check if settings were saved
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$settings_saved = isset( $_GET['settings-updated'] ) && sanitize_text_field( wp_unslash( $_GET['settings-updated'] ) ) === 'true';

		// Get existing options or initialize empty array
		$options = get_option( 'Lukic_snippet_codes_options', array() );

		// Get uninstall preference
		$cleanup_data = get_option( 'Lukic_snippet_codes_cleanup', 'preserve' );

		// Get all unique tags for filtering
		$all_tags = array();
		foreach ( $snippets_by_category as $category_snippets ) {
			foreach ( $category_snippets as $snippet_id => $snippet_data ) {
				foreach ( $snippet_data['tags'] as $tag ) {
					if ( ! isset( $all_tags[ $tag ] ) ) {
						$all_tags[ $tag ] = 1;
					} else {
						++$all_tags[ $tag ];
					}
				}
			}
		}

		// Sort tags by count (most used first)
		arsort( $all_tags );

		// Get top 8 tags for buttons
		$top_tags = array_slice( $all_tags, 0, 8, true );

		// Get all tags alphabetically for dropdown
		$all_tags_list = array_keys( $all_tags );
		sort( $all_tags_list );

		// Calculate total snippets and active snippets count for header
		$total_snippets  = 0;
		$active_snippets = 0;
		foreach ( $snippets_by_category as $category_snippets ) {
			$total_snippets += count( $category_snippets );
			foreach ( $category_snippets as $snippet_id => $snippet_data ) {
				if ( isset( $options[ $snippet_id ] ) && $options[ $snippet_id ] == 1 ) {
					++$active_snippets;
				}
			}
		}

		// Prepare stats for header
		$stats = array(
			array(
				'count' => $active_snippets,
				'label' => __( 'Active Snippets', 'lukic-code-snippets' ),
			),
			array(
				'count' => $total_snippets,
				'label' => __( 'Total Snippets', 'lukic-code-snippets' ),
			),
		);

		// Output the settings page HTML
		?>
		<div class="wrap Lukic-container Lukic-wrap">
			<?php
if ( ! defined( 'ABSPATH' ) ) exit;
			// Display header using the loaded component
			Lukic_display_header( __( 'List of all Snippets', 'lukic-code-snippets' ), $stats );
			?>
			
			<?php if ( $settings_saved ) : ?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( 'Settings saved.', 'lukic-code-snippets' ); ?></p>
				</div>
			<?php endif; ?>
			
			<div class="Lukic-settings-layout" style="display: flex; flex-wrap: wrap; gap: 20px; align-items: flex-start;">
				<div class="Lukic-settings-main" style="flex: 1; min-width: 0;">
					<div class="Lukic-settings-container" style="margin-top: 0;">
						<form method="post" action="options.php">
							<?php
if ( ! defined( 'ABSPATH' ) ) exit;
							settings_fields( 'Lukic_code_snippets' );
							?>
							
							<div class="Lukic-filter-controls">
								<div style="display: flex; align-items: center; justify-content: flex-end; gap: 20px; width: 100%;">
									<div class="Lukic-search-bar" style="margin-right: 0; max-width: 300px;">
										<input type="text" id="snippet-search" placeholder="<?php esc_attr_e( 'Search...', 'lukic-code-snippets' ); ?>">
										<span class="dashicons dashicons-search Lukic-search-icon"></span>
									</div>
									
									<button type="button" id="lukic-toggle-all-snippets" class="Lukic-btn Lukic-btn--secondary" style="display: flex; align-items: center; gap: 8px; cursor: pointer; padding: 6px 16px; white-space: nowrap; line-height: 1;">
										<span class="dashicons dashicons-update" style="font-size: 16px; width: 16px; height: 16px;"></span>
										<span style="font-size: 14px;"><?php esc_html_e( 'Toggle All', 'lukic-code-snippets' ); ?></span>
									</button>
								</div>
							</div>
							
							<div class="Lukic-tag-buttons">
								<span class="Lukic-tag-button active" data-tag=""><?php esc_html_e( 'All', 'lukic-code-snippets' ); ?></span>
								<span class="Lukic-tag-button" data-tag="active" data-filter-type="status"><?php esc_html_e( 'Active', 'lukic-code-snippets' ); ?></span>
								<?php foreach ( $top_tags as $tag => $count ) : ?>
									<span class="Lukic-tag-button" data-tag="<?php echo esc_attr( $tag ); ?>"><?php echo esc_html( ucfirst( $tag ) ); ?></span>
								<?php endforeach; ?>
							</div>
							
							<div class="Lukic-categories">
								<?php foreach ( $categories as $category_id => $category_data ) : ?>
									<div class="Lukic-category" id="category-<?php echo esc_attr( $category_id ); ?>">
										<div class="Lukic-category-header">
											<h2>
												<span class="dashicons <?php echo esc_attr( $category_data['icon'] ); ?>"></span>
												<?php echo esc_html( $category_data['name'] ); ?>
											</h2>
										</div>
										
										<div class="Lukic-snippets">
											<?php
if ( ! defined( 'ABSPATH' ) ) exit;
											if ( isset( $snippets_by_category[ $category_id ] ) ) {
												foreach ( $snippets_by_category[ $category_id ] as $snippet_id => $snippet_data ) :
													$is_checked = isset( $options[ $snippet_id ] ) && $options[ $snippet_id ] == 1;
													?>
													<div class="Lukic-snippet" data-tags="<?php echo esc_attr( implode( ' ', $snippet_data['tags'] ) ); ?>" data-tag-list="<?php echo esc_attr( implode( ',', $snippet_data['tags'] ) ); ?>" data-active="<?php echo esc_attr( $is_checked ? 'true' : 'false' ); ?>">
														<div class="Lukic-snippet-header">
														   
															<h3><?php echo esc_html( $snippet_data['name'] ); ?></h3>

															<label class="Lukic-switch">
																<input type="checkbox" name="Lukic_snippet_codes_options[<?php echo esc_attr( $snippet_id ); ?>]" value="1" <?php checked( $is_checked ); ?>>
																<span class="Lukic-slider"></span>
															</label>
														</div>
														<div class="Lukic-snippet-description">
															<p><?php echo esc_html( $this->get_snippet_description( $snippet_id ) ); ?></p>
															<div class="Lukic-tags">
																<?php foreach ( $snippet_data['tags'] as $tag ) : ?>
																	<span class="Lukic-tag"><?php echo esc_html( $tag ); ?></span>
																<?php endforeach; ?>
																<?php if ( ! empty( $snippet_data['warning'] ) ) : ?>
																	<span class="Lukic-tag Lukic-tag--warning">
																		<span class="dashicons dashicons-warning"></span>
																		<span><?php echo esc_html( $snippet_data['warning'] ); ?></span>
																	</span>
																<?php endif; ?>
															</div>
														</div>
													</div>
													<?php
if ( ! defined( 'ABSPATH' ) ) exit;
												endforeach;
											}
											?>
										</div>
									</div>
								<?php endforeach; ?>
							</div>
							
							<div style="margin-top: 30px; padding: 20px; background: #f9f9f9; border-radius: 6px; border-left: 4px solid #00E1AF;">
								<p style="margin: 0 0 10px 0; font-weight: 600; color: #333;">
									<span class="dashicons dashicons-info" style="color: #00E1AF; margin-right: 5px;"></span>
									<?php esc_html_e( 'Auto-Save Enabled', 'lukic-code-snippets' ); ?>
								</p>
								<p style="margin: 0 0 15px 0; color: #666; font-size: 14px;">
									<?php esc_html_e( 'Snippet settings are automatically saved when you toggle switches. The button below is for manual saves if needed.', 'lukic-code-snippets' ); ?>
								</p>
								<?php submit_button( __( 'Manual Save All', 'lukic-code-snippets' ), 'secondary', 'submit', true, array( 'style' => 'background-color: #f7f7f7; border-color: #ccc; color: #666;' ) ); ?>
							</div>
						</form>
					</div>
				</div>
				
				<div class="Lukic-settings-sidebar" style="width: 280px; flex-shrink: 0;">
					<div class="Lukic-sidebar-widget" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border: 1px solid #e2e8f0; text-align: center; border-top: 4px solid #00E1AF;">
						<div style="width: 48px; height: 48px; margin: 0 auto 15px; background: rgba(0, 225, 175, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center;">
							<span class="dashicons dashicons-admin-customizer" style="color: #00E1AF; font-size: 24px; width: 24px; height: 24px;"></span>
						</div>
						<h3 style="margin-top: 0; font-size: 16px; font-weight: 600; color: #1e293b;"><?php esc_html_e( 'Need Custom Work?', 'lukic-code-snippets' ); ?></h3>
						<p style="font-size: 14px; color: #64748b; line-height: 1.5; margin-bottom: 20px;"><?php esc_html_e( 'I offer professional WordPress custom development, performance optimization, and plugin adjustments.', 'lukic-code-snippets' ); ?></p>
						<a href="https://wplukic.com" target="_blank" rel="noopener noreferrer" class="Lukic-btn Lukic-btn--primary" style="display: block; text-decoration: none; padding: 10px; background-color: #00E1AF; color: #fff; border-radius: 4px; font-weight: 600; transition: background-color 0.2s;"><?php esc_html_e( 'About the Author', 'lukic-code-snippets' ); ?></a>
					</div>
					
					<div class="Lukic-sidebar-widget" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border: 1px solid #e2e8f0; text-align: center; margin-top: 20px;">
						<h3 style="margin-top: 0; font-size: 16px; font-weight: 600; color: #1e293b; display: flex; align-items: center; justify-content: center; gap: 5px;">
							<span class="dashicons dashicons-star-filled" style="color: #fbbf24; font-size: 18px; width: 18px; height: 18px;"></span>
							<?php esc_html_e( 'Show Your Support', 'lukic-code-snippets' ); ?>
						</h3>
						<p style="font-size: 14px; color: #64748b; line-height: 1.5; margin-bottom: 20px;"><?php esc_html_e( 'If you find this plugin useful, please consider leaving a 5-star review on WordPress.org. It helps a lot!', 'lukic-code-snippets' ); ?></p>
						<a href="https://wordpress.org/plugins/lukic-code-snippets/" target="_blank" rel="noopener noreferrer" style="color: #00E1AF; text-decoration: none; font-weight: 600;"><?php esc_html_e( 'Leave a Review &rarr;', 'lukic-code-snippets' ); ?></a>
					</div>
				</div>
			</div>
		</div>
		<?php
		if ( ! defined( 'ABSPATH' ) ) exit;
	}

	/**
	 * Display plugin settings page
	 */
	public function display_plugin_settings_page() {
		// Check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Get uninstall preference
		$cleanup_data = get_option( 'Lukic_snippet_codes_cleanup', 'preserve' );

		// Check if settings were saved
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$settings_saved = isset( $_GET['settings-updated'] ) && sanitize_text_field( wp_unslash( $_GET['settings-updated'] ) ) === 'true';
		?>
				<div class="wrap Lukic-wrap">
			<?php
if ( ! defined( 'ABSPATH' ) ) exit;
			// Display header using the loaded component
			Lukic_display_header( __( 'Plugin Settings', 'lukic-code-snippets' ), array() );
			?>
			
			<?php if ( $settings_saved ) : ?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( 'Settings saved.', 'lukic-code-snippets' ); ?></p>
				</div>
			<?php endif; ?>
			
			<div class="Lukic-settings-container">
				<form method="post" action="options.php">
					<?php
if ( ! defined( 'ABSPATH' ) ) exit;
					settings_fields( 'Lukic_code_snippets_settings' );
					?>
					
					<h2><?php esc_html_e( 'Plugin Behavior', 'lukic-code-snippets' ); ?></h2>
					
					<table class="form-table">
						<tr>
							<th scope="row"><?php esc_html_e( 'Plugin Uninstallation', 'lukic-code-snippets' ); ?></th>
							<td>
								<fieldset>
									<legend class="screen-reader-text"><span><?php esc_html_e( 'Plugin Uninstallation', 'lukic-code-snippets' ); ?></span></legend>
									<label for="cleanup_preserve">
										<input type="radio" id="cleanup_preserve" name="Lukic_snippet_codes_cleanup" value="preserve" <?php checked( $cleanup_data, 'preserve' ); ?>>
										<?php esc_html_e( 'Preserve data when plugin is deleted (recommended)', 'lukic-code-snippets' ); ?>
									</label><br>
									<label for="cleanup_delete">
										<input type="radio" id="cleanup_delete" name="Lukic_snippet_codes_cleanup" value="delete" <?php checked( $cleanup_data, 'delete' ); ?>>
										<?php esc_html_e( 'Delete all plugin data when plugin is deleted (tables, options, etc.)', 'lukic-code-snippets' ); ?>
									</label>
									<p class="description"><?php esc_html_e( 'This setting determines what happens to your data when you delete the plugin. If you choose to delete all data, any custom tables created by the snippets (like redirect tables) will be permanently removed.', 'lukic-code-snippets' ); ?></p>
								</fieldset>
							</td>
						</tr>
					</table>
					
					<?php submit_button( __( 'Save Settings', 'lukic-code-snippets' ), 'primary', 'submit', true, array( 'style' => 'background-color: #00E1AF; border-color: #00E1AF;' ) ); ?>
				</form>
			</div>
		</div>
		<?php
if ( ! defined( 'ABSPATH' ) ) exit;
	}

	/**
	 * Get category icon
	 */
	private function get_category_icon( $category ) {
		$icons = array(
			'content'  => 'edit',
			'admin'    => 'admin-appearance',
			'media'    => 'format-image',
			'security' => 'shield',
		);

		return isset( $icons[ $category ] ) ? $icons[ $category ] : 'admin-generic';
	}

	/**
	 * Get snippet description
	 */
	private function get_snippet_description( $snippet_id ) {
		$snippet = Lukic_Snippet_Registry::get_snippet( $snippet_id );
		return ( $snippet && isset( $snippet['description'] ) ) ? $snippet['description'] : '';
	}

	/**
	 * Enqueue auto-save script for the main settings page
	 */
	public function enqueue_auto_save_script( $hook ) {
		// Only load on the main settings page
		if ( 'toplevel_page_lukic-code-snippets' !== $hook ) {
			return;
		}

		// Register and enqueue auto-save script
		wp_enqueue_script(
			'Lukic-auto-save',
			plugin_dir_url( __DIR__ ) . 'assets/js/auto-save.js',
			array( 'jquery' ),
			Lukic_SNIPPET_CODES_VERSION,
			true
		);

		// Localize the script
		wp_localize_script(
			'Lukic-auto-save',
			'Lukic_auto_save',
			array(
				'nonce'              => wp_create_nonce( 'Lukic_auto_save_nonce' ),
				'activated'          => __( 'Activated', 'lukic-code-snippets' ),
				'deactivated'        => __( 'Deactivated', 'lukic-code-snippets' ),
				'error_saving'       => __( 'Error saving settings', 'lukic-code-snippets' ),
				'refreshing_message' => __( 'Refreshing page to apply changes...', 'lukic-code-snippets' ),
			)
		);
	}

	/**
	 * AJAX handler for auto-saving snippet settings
	 */
	public function ajax_auto_save_snippet() {
		// Verify nonce and capabilities
		check_ajax_referer( 'Lukic_auto_save_nonce', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Insufficient permissions', 'lukic-code-snippets' ), 403 );
		}

		if ( ! isset( $_POST['options'] ) ) {
			wp_send_json_error( __( 'Missing options payload.', 'lukic-code-snippets' ), 400 );
		}

		$options_json = sanitize_text_field( wp_unslash( $_POST['options'] ) );
		$options      = json_decode( $options_json, true );
		if ( json_last_error() !== JSON_ERROR_NONE || ! is_array( $options ) ) {
			wp_send_json_error( __( 'Invalid JSON data.', 'lukic-code-snippets' ), 400 );
		}

		$sanitized_options  = array();
		$available_snippets = Lukic_Snippet_Registry::get_snippets();
		foreach ( $available_snippets as $snippet_id => $snippet_data ) {
			$value                            = isset( $options[ $snippet_id ] ) ? (int) $options[ $snippet_id ] : 0;
			$sanitized_options[ $snippet_id ] = $value === 1 ? 1 : 0;
		}

		$updated = update_option( 'Lukic_snippet_codes_options', $sanitized_options );
		if ( ! $updated && get_option( 'Lukic_snippet_codes_options' ) !== $sanitized_options ) {
			wp_send_json_error( __( 'Failed to save settings.', 'lukic-code-snippets' ) );
		}

		wp_send_json_success( __( 'Settings saved successfully.', 'lukic-code-snippets' ) );
	}
}
