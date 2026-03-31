<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Snippet: Content Order
 * Description: Enables custom ordering of hierarchical content types or those supporting page attributes
 */

/**
 * Get all post types that support ordering
 */
function Lukic_get_orderable_post_types() {
	// Default post types we always want to enable
	$default_post_types = array( 'post', 'page' );

	// Get all public custom post types
	$args              = array(
		'public'   => true,
		'_builtin' => false,
	);
	$custom_post_types = get_post_types( $args, 'names', 'and' );

	// Combine default and custom post types
	$post_types = array_merge( $default_post_types, $custom_post_types );

	// Filter to allow themes/plugins to modify the list
	return array_values( array_filter( array_map( 'sanitize_key', apply_filters( 'Lukic_content_order_post_types', $post_types ) ) ) );
}

/**
 * Check if frontend ordering is enabled for a post type
 */
function Lukic_is_frontend_ordering_enabled( $post_type ) {
	$settings = get_option( 'Lukic_content_order_settings', array() );
	$key      = 'frontend_' . sanitize_key( $post_type );
	return isset( $settings[ $key ] ) ? (bool) $settings[ $key ] : false;
}

/**
 * Resolve the requested order screen post type from the page slug.
 *
 * @param string $page Page slug.
 * @return string
 */
function Lukic_content_order_resolve_post_type_from_page( $page ) {
	$page = sanitize_key( $page );

	if ( 'lukic-order-post' === $page ) {
		return 'post';
	}

	if ( 'lukic-order-page' === $page ) {
		return 'page';
	}

	if ( strpos( $page, 'lukic-order-' ) === 0 ) {
		return sanitize_key( str_replace( 'lukic-order-', '', $page ) );
	}

	return '';
}

/**
 * Add submenu items for ordering posts, pages and custom post types
 */
function Lukic_content_order_add_submenus() {
	// Add settings page under main plugin menu
	add_submenu_page(
		'lukic-code-snippets',
		__( 'Content Order Settings', 'lukic-code-snippets' ),
		__( 'Order Settings', 'lukic-code-snippets' ),
		'manage_options',
		'lukic-order-settings',
		'Lukic_content_order_settings_page'
	);

	// Get all post types that support ordering
	$post_types = Lukic_get_orderable_post_types();

	foreach ( $post_types as $post_type ) {
		if ( ! post_type_exists( $post_type ) ) {
			continue;
		}

		$post_type_obj = get_post_type_object( $post_type );
		$capability    = $post_type_obj->cap->edit_posts;

		// Different parent menu depending on post type
		if ( $post_type === 'post' ) {
			add_submenu_page(
				'edit.php',
				__( 'Order Posts', 'lukic-code-snippets' ),
				__( 'Order', 'lukic-code-snippets' ),
				$capability,
				'lukic-order-post',
				'Lukic_content_order_interface'
			);
		} else {
			add_submenu_page(
				'edit.php?post_type=' . $post_type,
				/* translators: %s: Post type name (e.g., Posts, Pages) */
				sprintf( __( 'Order %s', 'lukic-code-snippets' ), $post_type_obj->labels->name ),
				__( 'Order', 'lukic-code-snippets' ),
				$capability,
				'lukic-order-' . $post_type,
				'Lukic_content_order_interface'
			);
		}
	}
}
add_action( 'admin_menu', 'Lukic_content_order_add_submenus', 100 );

/**
 * Set default ordering for posts, pages and custom post types
 */
function Lukic_content_order_set_default_order( $query ) {
	// In admin, only modify main queries
	if ( is_admin() ) {
		if ( ! $query->is_main_query() ) {
			return $query;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$orderby = isset( $_GET['orderby'] ) ? sanitize_text_field( wp_unslash( $_GET['orderby'] ) ) : '';
		if ( ! empty( $orderby ) ) {
			return $query;
		}

		$post_type = $query->get( 'post_type' );

		// Special case for posts main query in admin
		if ( empty( $post_type ) && isset( $query->query['post_type'] ) && $query->query['post_type'] === '' ) {
			$post_type = 'post';
		}

		// Get all orderable post types
		$orderable_post_types = Lukic_get_orderable_post_types();

		if ( is_string( $post_type ) && in_array( $post_type, $orderable_post_types, true ) ) {
			$query->set( 'orderby', 'menu_order title' );
			$query->set( 'order', 'ASC' );
		}

		return $query;
	}

	// On frontend, apply to ALL queries if frontend ordering is enabled
	$post_type = $query->get( 'post_type' );

	// Default to 'post' if no post type is set
	if ( is_array( $post_type ) ) {
		return $query;
	}

	if ( empty( $post_type ) ) {
		$post_type = 'post';
	}

	// Get all orderable post types
	$orderable_post_types = Lukic_get_orderable_post_types();

	// Only apply if this is an orderable post type and frontend ordering is enabled
	if ( in_array( $post_type, $orderable_post_types, true ) && Lukic_is_frontend_ordering_enabled( $post_type ) ) {
		// Don't override if a specific orderby is already set (unless it's 'date')
		$current_orderby = $query->get( 'orderby' );
		if ( empty( $current_orderby ) || $current_orderby === 'date' ) {
			$query->set( 'orderby', 'menu_order title' );
			$query->set( 'order', 'ASC' );
		}
	}

	return $query;
}
add_filter( 'pre_get_posts', 'Lukic_content_order_set_default_order', 20 );

/**
 * Filter Gutenberg block query arguments to apply custom ordering
 */
function Lukic_content_order_block_query_args( $query, $block, $page ) {
	// Only on frontend
	if ( is_admin() ) {
		return $query;
	}

	// Check if this block queries posts
	if ( ! isset( $query['post_type'] ) ) {
		return $query;
	}

	$post_type = $query['post_type'];
	if ( is_array( $post_type ) ) {
		return $query;
	}

	// Get all orderable post types
	$orderable_post_types = Lukic_get_orderable_post_types();

	// Apply custom ordering if enabled for this post type
	if ( in_array( $post_type, $orderable_post_types, true ) && Lukic_is_frontend_ordering_enabled( $post_type ) ) {
		// Only override if no specific orderby is set, or if it's set to 'date'
		if ( ! isset( $query['orderby'] ) || $query['orderby'] === 'date' ) {
			$query['orderby'] = 'menu_order title';
			$query['order']   = 'ASC';
		}
	}

	return $query;
}
add_filter( 'query_loop_block_query_vars', 'Lukic_content_order_block_query_args', 20, 3 );

/**
 * Enqueue scripts for the content order page
 */
function Lukic_content_order_scripts( $hook ) {
	// Check if we're on one of our order pages
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
	if ( strpos( $page, 'lukic-order-' ) === 0 ) {
		// CSS is now handled by the main plugin

		// Enqueue jQuery UI for sortable functionality
		wp_enqueue_script( 'jquery-ui-sortable' );

		// Enqueue the custom JavaScript file
		wp_enqueue_script(
			'Lukic-content-order-script',
			plugin_dir_url( __DIR__ ) . 'assets/js/content-order.js',
			array( 'jquery', 'jquery-ui-sortable' ),
			Lukic_SNIPPET_CODES_VERSION,
			true
		);

		wp_localize_script(
			'Lukic-content-order-script',
			'LukicContentOrder',
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'Lukic_content_order_nonce' ),
				'loading' => __( 'Updating order...', 'lukic-code-snippets' ),
				'success' => __( 'Order updated successfully.', 'lukic-code-snippets' ),
				'error'   => __( 'Error updating order.', 'lukic-code-snippets' ),
			)
		);
	}
}
add_action( 'admin_enqueue_scripts', 'Lukic_content_order_scripts' );

/**
 * AJAX callback to update post order
 */
function Lukic_update_post_order() {
	check_ajax_referer( 'Lukic_content_order_nonce', 'nonce' );

	// Get and validate data
	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$post_type = isset( $_POST['post_type'] ) ? sanitize_key( wp_unslash( $_POST['post_type'] ) ) : '';
	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$post_ids  = isset( $_POST['post_ids'] ) ? array_values( array_filter( array_map( 'absint', wp_unslash( (array) $_POST['post_ids'] ) ) ) ) : array();

	if ( empty( $post_type ) || empty( $post_ids ) || ! post_type_exists( $post_type ) || ! in_array( $post_type, Lukic_get_orderable_post_types(), true ) ) {
		wp_send_json_error( array( 'message' => __( 'Invalid data received.', 'lukic-code-snippets' ) ) );
	}

	$post_type_obj = get_post_type_object( $post_type );
	$capability    = ( $post_type_obj && isset( $post_type_obj->cap->edit_posts ) ) ? $post_type_obj->cap->edit_posts : 'edit_posts';

	if ( ! current_user_can( $capability ) ) {
		wp_send_json_error( array( 'message' => __( 'You do not have permission to do this.', 'lukic-code-snippets' ) ) );
	}

	foreach ( $post_ids as $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post || $post->post_type !== $post_type || ! current_user_can( 'edit_post', $post_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid post list received.', 'lukic-code-snippets' ) ) );
		}
	}

	// Update order
	$success = true;
	foreach ( $post_ids as $menu_order => $post_id ) {
		// Update menu order
		$updated_post = array(
			'ID'         => $post_id,
			'menu_order' => $menu_order,
		);

		$result = wp_update_post( $updated_post );
		if ( ! $result ) {
			$success = false;
		}
	}

	if ( $success ) {
		$http_referer = wp_get_referer();
		if ( ! $http_referer ) {
			$http_referer = $post_type === 'post'
				? admin_url( 'edit.php?page=lukic-order-post' )
				: admin_url( 'edit.php?post_type=' . $post_type . '&page=lukic-order-' . $post_type );
		}
		wp_send_json_success(
			array(
				'message'  => __( 'Order updated successfully.', 'lukic-code-snippets' ),
				'redirect' => add_query_arg( 'orderupdated', 'true', $http_referer ),
			)
		);
	} else {
		wp_send_json_error( array( 'message' => __( 'Error updating some items.', 'lukic-code-snippets' ) ) );
	}
}
add_action( 'wp_ajax_Lukic_update_post_order', 'Lukic_update_post_order' );

/**
 * Interface for the order page
 */
function Lukic_content_order_interface() {
	// Get current page slug
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';

	// Determine post type from page slug
	$post_type = Lukic_content_order_resolve_post_type_from_page( $page );

	// Final check for valid post type
	if ( empty( $post_type ) || ! post_type_exists( $post_type ) ) {
		wp_die( esc_html__( 'Invalid post type.', 'lukic-code-snippets' ) );
	}

	$post_type_obj = get_post_type_object( $post_type );

	// Prepare additional query arguments based on post type
	$query_args = array(
		'post_type'      => $post_type,
		'posts_per_page' => -1,
		'orderby'        => 'menu_order title',
		'order'          => 'ASC',
		'post_status'    => 'publish',
	);

	// Add hierarchical support for pages and hierarchical post types
	$is_hierarchical = is_post_type_hierarchical( $post_type );

	// Allow filtering posts by parent
	$current_parent = 0;
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( $is_hierarchical && isset( $_GET['parent'] ) ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$current_parent            = absint( wp_unslash( $_GET['parent'] ) );
		$query_args['post_parent'] = $current_parent;
	}

	// Get posts to order
	$posts = get_posts( $query_args );

	// Get success message if posts were ordered
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$order_updated = isset( $_GET['orderupdated'] ) && sanitize_text_field( wp_unslash( $_GET['orderupdated'] ) ) === 'true';

	// Get parents for breadcrumb navigation in hierarchical post types
	$parents = array();
	if ( $is_hierarchical && $current_parent > 0 ) {
		$parent_id = $current_parent;
		while ( $parent_id ) {
			$parent = get_post( $parent_id );
			if ( $parent ) {
				$parents[] = $parent;
				$parent_id = $parent->post_parent;
			} else {
				$parent_id = 0;
			}
		}
		$parents = array_reverse( $parents );
	}

	// Output the interface
	?>
	<div class="wrap Lukic-content-order">
		<?php
		// Header component is already loaded in main plugin file

		// Display the header with stats
		$stats = array(
			__( 'Post Type', 'lukic-code-snippets' ) => $post_type_obj->labels->name,
			__( 'Items', 'lukic-code-snippets' )     => count( $posts ),
		);

		/* translators: %s: Post type name (e.g., Posts, Pages) */
		Lukic_display_header( sprintf( __( 'Order %s', 'lukic-code-snippets' ), $post_type_obj->labels->name ), $stats );
		?>
		
		<?php if ( $order_updated ) : ?>
			<div class="notice notice-success is-dismissible">
				<p><?php esc_html_e( 'Order updated successfully.', 'lukic-code-snippets' ); ?></p>
			</div>
		<?php endif; ?>
		
		<div class="Lukic-content-order-instructions">
			<p><span class="dashicons dashicons-info"></span> <?php esc_html_e( 'Drag and drop items to change their order. Changes are saved automatically.', 'lukic-code-snippets' ); ?></p>
		</div>
		
		<!-- Status message area -->
		<div class="Lukic-content-order-status"></div>
		
		<?php if ( $is_hierarchical ) : ?>
			<!-- Breadcrumb navigation for hierarchical types -->
			<div class="Lukic-content-order-breadcrumb">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . $page ) ); ?>"><?php echo esc_html( $post_type_obj->labels->name ); ?></a>
				
				<?php foreach ( $parents as $parent ) : ?>
					&raquo; 
					<a href="<?php echo esc_url( add_query_arg( 'parent', $parent->ID ) ); ?>">
						<?php echo esc_html( $parent->post_title ); ?>
					</a>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
		
		<?php if ( empty( $posts ) ) : ?>
			<div class="Lukic-content-order-empty">
				<p><?php esc_html_e( 'No items found to order.', 'lukic-code-snippets' ); ?></p>
				
				<?php if ( $current_parent ) : ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . $page ) ); ?>" class="button">
						<?php esc_html_e( 'Back to Top Level', 'lukic-code-snippets' ); ?>
					</a>
				<?php endif; ?>
			</div>
		<?php else : ?>
			<ul class="Lukic-content-order-list" data-post-type="<?php echo esc_attr( $post_type ); ?>">
				<?php
				$count = 1;
				foreach ( $posts as $post ) :
					// Get permalink for view link
					$permalink = get_permalink( $post->ID );

					// Check if this post has children (for hierarchical types)
					$has_children = false;
					if ( $is_hierarchical ) {
						$children     = get_posts(
							array(
								'post_type'      => $post_type,
								'posts_per_page' => 1,
								'post_parent'    => $post->ID,
								'fields'         => 'ids',
							)
						);
						$has_children = ! empty( $children );
					}
					?>
					<li class="Lukic-content-order-item" data-id="<?php echo esc_attr( $post->ID ); ?>" data-permalink="<?php echo esc_url( $permalink ); ?>">
						<!-- Order number -->
						<span class="Lukic-content-order-number"><?php echo esc_html( $count ); ?></span>
						
						<!-- Drag handle -->
						<span class="Lukic-content-order-handle dashicons dashicons-menu"></span>
						
						<!-- Post title -->
						<span class="Lukic-content-order-title"><?php echo esc_html( $post->post_title ); ?></span>
						
						<?php if ( $is_hierarchical ) : ?>
							<!-- Child items link for hierarchical types -->
							<?php if ( $has_children ) : ?>
								<a href="<?php echo esc_url( add_query_arg( 'parent', $post->ID ) ); ?>" class="Lukic-content-order-children">
									<span class="dashicons dashicons-category"></span>
									<?php esc_html_e( 'View Children', 'lukic-code-snippets' ); ?>
								</a>
							<?php endif; ?>
						<?php endif; ?>
					</li>
					<?php
					++$count;
				endforeach;
				?>
			</ul>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Settings page for Content Order
 */
function Lukic_content_order_settings_page() {
	// Check user permissions
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'lukic-code-snippets' ) );
	}

	// Handle form submission
	$settings_updated = false;
	if ( isset( $_POST['Lukic_content_order_save_settings'] ) && check_admin_referer( 'Lukic_content_order_settings' ) ) {
		$settings   = array();
		$post_types = Lukic_get_orderable_post_types();

		foreach ( $post_types as $post_type ) {
			$key              = 'frontend_' . $post_type;
			$settings[ $key ] = isset( $_POST[ $key ] ) ? 1 : 0;
		}

		update_option( 'Lukic_content_order_settings', $settings );
		$settings_updated = true;
	}

	// Get current settings
	$current_settings = get_option( 'Lukic_content_order_settings', array() );
	$post_types       = Lukic_get_orderable_post_types();

	// Prepare stats for header
	$stats = array(
		array(
			'count' => count( $post_types ),
			'label' => __( 'Post Types', 'lukic-code-snippets' ),
		),
		array(
			'count' => __( 'Active', 'lukic-code-snippets' ),
			'label' => __( 'Status', 'lukic-code-snippets' ),
		),
	);

	?>
	<div class="wrap Lukic-admin-page">
		<?php Lukic_display_header( __( 'Content Order Settings', 'lukic-code-snippets' ), $stats ); ?>
		
		<div class="Lukic-container">
			<div class="Lukic-content-wrapper">
				<div class="Lukic-content">
					
					<?php if ( $settings_updated ) : ?>
						<div class="Lukic-notice Lukic-notice-success">
							<p><?php esc_html_e( 'Settings saved successfully.', 'lukic-code-snippets' ); ?></p>
						</div>
					<?php endif; ?>
					
					<div class="Lukic-card" style="padding: 20px;">
						<div class="Lukic-card-header">
							<h2><?php esc_html_e( 'Frontend Ordering Settings', 'lukic-code-snippets' ); ?></h2>
							<p class="Lukic-description">
								<?php esc_html_e( 'Enable custom ordering on the frontend for each post type. When enabled, the custom order you set in the admin will be applied to all frontend queries including blog pages, archives, Latest Posts blocks, and Query Loop blocks.', 'lukic-code-snippets' ); ?>
							</p>
						</div>
						
						<div class="Lukic-card-body">
							<form method="post" action="">
								<?php wp_nonce_field( 'Lukic_content_order_settings' ); ?>
								
								<table class="form-table" role="presentation">
									<tbody>
										<?php foreach ( $post_types as $post_type ) : ?>
											<?php
											if ( ! post_type_exists( $post_type ) ) {
												continue;
											}

											$post_type_obj = get_post_type_object( $post_type );
											$key           = 'frontend_' . $post_type;
											$checked       = isset( $current_settings[ $key ] ) && $current_settings[ $key ];
											?>
											<tr>
												<th scope="row">
													<label for="<?php echo esc_attr( $key ); ?>">
														<?php echo esc_html( $post_type_obj->labels->name ); ?>
													</label>
												</th>
												<td>
													<fieldset>
														<label>
															<input 
																type="checkbox" 
																name="<?php echo esc_attr( $key ); ?>" 
																id="<?php echo esc_attr( $key ); ?>" 
																value="1" 
																<?php checked( $checked ); ?>
															/>
															<?php
															/* translators: %s: Post type name (e.g., Posts, Pages) */
															echo esc_html( sprintf( __( 'Apply custom ordering to %s on the frontend', 'lukic-code-snippets' ), strtolower( $post_type_obj->labels->name ) ) );
															?>
														</label>
														<p class="description">
															<?php
															/* translators: %s: Post type name (e.g., Posts, Pages) */
															echo esc_html( sprintf( __( 'When enabled, %s will display in your custom order on blog pages, archives, and Gutenberg block queries.', 'lukic-code-snippets' ), strtolower( $post_type_obj->labels->name ) ) );
															?>
														</p>
													</fieldset>
												</td>
											</tr>
										<?php endforeach; ?>
									</tbody>
								</table>
								
								<div class="Lukic-submit-container">
					<button type="submit" name="Lukic_content_order_save_settings" class="button button-primary">
						<?php esc_html_e( 'Save Settings', 'lukic-code-snippets' ); ?>
					</button>
					<p class="description"><?php esc_html_e( 'Changes will take effect immediately after saving.', 'lukic-code-snippets' ); ?></p>
				</div>
							</form>
						</div>
					</div>
					
					<div class="Lukic-card" style="padding: 20px; margin-top: 20px;">
						<div class="Lukic-card-header">
							<h2><?php esc_html_e( 'How It Works', 'lukic-code-snippets' ); ?></h2>
						</div>
						<div class="Lukic-card-body">
							<ul class="Lukic-info-list">
								<li>
									<span class="dashicons dashicons-admin-generic"></span>
									<strong><?php esc_html_e( 'Backend Ordering:', 'lukic-code-snippets' ); ?></strong>
									<?php esc_html_e( 'Always active. Use the "Order" submenu under each post type to drag and drop items into your preferred order.', 'lukic-code-snippets' ); ?>
								</li>
								<li>
									<span class="dashicons dashicons-admin-site-alt3"></span>
									<strong><?php esc_html_e( 'Frontend Ordering:', 'lukic-code-snippets' ); ?></strong>
									<?php esc_html_e( 'Enable above to apply your custom order to frontend pages, including blog pages, archives, and Gutenberg query blocks.', 'lukic-code-snippets' ); ?>
								</li>
								<li>
									<span class="dashicons dashicons-editor-code"></span>
									<strong><?php esc_html_e( 'Gutenberg Blocks:', 'lukic-code-snippets' ); ?></strong>
									<?php esc_html_e( 'Latest Posts blocks, Query Loop blocks, and Post List blocks will all respect your custom order when frontend ordering is enabled. Works even when the block is set to "Sort by Newest to Oldest".', 'lukic-code-snippets' ); ?>
								</li>
								<li>
									<span class="dashicons dashicons-info"></span>
									<strong><?php esc_html_e( 'Note:', 'lukic-code-snippets' ); ?></strong>
									<?php esc_html_e( 'Custom ordering uses the menu_order field. Some themes or plugins may override this with their own ordering.', 'lukic-code-snippets' ); ?>
								</li>
							</ul>
						</div>
					</div>
					
				</div>
			</div>
		</div>
	</div>
	<?php
}

/**
 * Add debug information to help with troubleshooting
 */
function Lukic_content_order_debug() {
	// Only show for admins
	if ( current_user_can( 'manage_options' ) ) {
		$screen     = get_current_screen();
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$page       = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
		$post_types = Lukic_get_orderable_post_types();

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- This is a debug HTML comment
		echo '<!-- Lukic Content Order snippet is active. Current screen: ' . esc_html( $screen ? $screen->id : '' ) . ', ' . esc_html( $screen ? $screen->base : '' ) . '. Page: ' . esc_html( $page ) . '. Post types: ' . esc_html( implode( ', ', $post_types ) ) . ' -->';
	}
}
add_action( 'admin_footer', 'Lukic_content_order_debug' );
