<?php
/**
 * Redirect Manager for Lukic Code Snippets
 *
 * Adds a redirect management system to WordPress with an admin interface.
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Only load if this snippet is activated
if ( ! function_exists( 'Lukic_redirect_manager_init' ) ) {

	/**
	 * Create custom database table for redirects
	 */
	function Lukic_redirect_manager_create_table() {
		global $wpdb;

		$table_name      = $wpdb->prefix . 'lukic_redirects';
		$charset_collate = $wpdb->get_charset_collate();

		// Check if table exists
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.DirectDatabaseQuery.SchemaChange
		if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) != $table_name ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange
			$sql = "CREATE TABLE $table_name (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                source_url varchar(255) NOT NULL,
                target_url varchar(255) NOT NULL,
                redirect_type smallint(4) NOT NULL DEFAULT 301,
                pattern_match tinyint(1) NOT NULL DEFAULT 0,
                status tinyint(1) NOT NULL DEFAULT 1,
                hits mediumint(9) NOT NULL DEFAULT 0,
                created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
                last_accessed datetime DEFAULT NULL,
                PRIMARY KEY  (id),
                KEY source_url (source_url)
            ) $charset_collate;";

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			dbDelta( $sql );
		} else {
			// Check if pattern_match column exists
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$column_exists = $wpdb->get_results( "SHOW COLUMNS FROM {$table_name} LIKE 'pattern_match';" );

			// If column doesn't exist, add it
			if ( empty( $column_exists ) ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.SchemaChange, PluginCheck.Security.DirectDB.UnescapedDBParameter
				$wpdb->query( "ALTER TABLE {$table_name} ADD COLUMN pattern_match tinyint(1) NOT NULL DEFAULT 0 AFTER redirect_type;" );
			}
		}
	}

	/**
	 * Initialize the redirect manager functionality
	 */
	function Lukic_redirect_manager_init() {
		// Create the database table on activation
		register_activation_hook( Lukic_SNIPPET_CODES_PLUGIN_DIR . 'Lukic-code-snippets.php', 'Lukic_redirect_manager_create_table' );

		// Also create the table when the snippet is loaded (in case it wasn't created on activation)
		Lukic_redirect_manager_create_table();

		// Add admin menu
		add_action( 'admin_menu', 'Lukic_redirect_manager_add_menu' );

		// Handle redirect logic on template_redirect
		add_action( 'template_redirect', 'Lukic_redirect_manager_process_redirects', 1 );

		// Add AJAX handlers
		add_action( 'wp_ajax_Lukic_save_redirect', 'Lukic_redirect_manager_save_redirect' );
		add_action( 'wp_ajax_Lukic_delete_redirect', 'Lukic_redirect_manager_delete_redirect' );

		// Add settings save handler
		add_action( 'wp_ajax_Lukic_save_redirect_settings', 'Lukic_redirect_manager_save_settings' );

		// Enqueue scripts and styles
		add_action( 'admin_enqueue_scripts', 'Lukic_redirect_manager_enqueue_assets' );
	}
	Lukic_redirect_manager_init();

	/**
	 * Enqueue admin scripts and styles
	 */
	function Lukic_redirect_manager_enqueue_assets( $hook ) {
		if ( strpos( $hook, 'lukic-redirect-manager' ) === false ) {
			return;
		}

		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'jquery-ui-tabs' );
		wp_enqueue_script( 'jquery-ui-dialog' );
		wp_enqueue_style( 'wp-jquery-ui-dialog' );

		wp_localize_script(
			'Lukic-redirect-manager',
			'Lukic_redirect_vars',
			array(
				'ajax_url'       => admin_url( 'admin-ajax.php' ),
				'nonce'          => wp_create_nonce( 'Lukic_redirect_nonce' ),
				'confirm_delete' => __( 'Are you sure you want to delete this redirect?', 'lukic-code-snippets' ),
			)
		);

		wp_add_inline_style( 'Lukic-admin-styles', '
			:root { --Lukic-primary: #00E1AF; --Lukic-primary-hover: #00c99e; --Lukic-dark-bg: #272727; --Lukic-dark-surface: #444444; --Lukic-white: #ffffff; --Lukic-space-4: 1rem; --Lukic-space-5: 1.25rem; --Lukic-border-radius: 0.25rem; --Lukic-shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }
			.wpl-code-snippets-header { background: var(--Lukic-dark-bg); border-radius: var(--Lukic-border-radius); margin-bottom: var(--Lukic-space-5); padding: var(--Lukic-space-5); }
			.wpl-code-snippets-header__content { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: var(--Lukic-space-4); }
			.wpl-code-snippets-header__brand h2 { color: var(--Lukic-white); font-size: 2.5rem; margin: 0; font-weight: 700; }
			.wpl-code-snippets-header__brand h2 span { color: var(--Lukic-primary); }
			.wpl-code-snippets-header__stats { display: flex; gap: 15px; flex-wrap: wrap; }
			.wpl-code-snippets-header__stats-item { background: var(--Lukic-dark-surface); color: var(--Lukic-white); padding: 15px 20px; border-radius: var(--Lukic-border-radius); text-align: center; min-width: 100px; }
			.wpl-code-snippets-header__stats-item-count { color: var(--Lukic-primary); font-size: 1.25rem; font-weight: 700; }
			.Lukic-wrap h1 { margin-bottom: 20px; color: #23282d; }
			.Lukic-tabs { margin-top: 20px; }
			.Lukic-nav-tabs { margin-bottom: 0; border-bottom: 1px solid #ccd0d4; }
			.Lukic-nav-tabs .nav-tab { margin-bottom: -1px; padding: 10px 15px; font-size: 14px; font-weight: 500; transition: all 0.2s ease; }
			.Lukic-nav-tabs .nav-tab-active, .Lukic-nav-tabs .nav-tab:hover { background-color: #fff; border-bottom-color: #fff; color: #00E1AF; }
			.tab-content { background: #fff; border: 1px solid #ccd0d4; border-top: none; padding: 20px; box-shadow: 0 1px 1px rgba(0,0,0,0.04); }
			.Lukic-table-container { margin-top: 20px; }
			.wp-list-table { border-collapse: collapse; width: 100%; }
			.wp-list-table th { font-weight: 600; text-align: left; padding: 10px; }
			.wp-list-table td { padding: 12px 10px; vertical-align: middle; }
			.wp-list-table tr:hover { background-color: #f8f8f8; }
			.button-primary, .button-primary:focus { background-color: #00E1AF !important; border-color: #00E1AF !important; color: #fff !important; box-shadow: none !important; }
			.button-primary:hover { background-color: #00c99d !important; border-color: #00c99d !important; }
			.search-input { padding: 6px 10px; min-width: 250px; border: 1px solid #ddd; border-radius: 4px; margin-right: 5px; height: 40px; }
			#redirect-filter-type, #redirect-filter-status, #redirect-filter-pattern { padding: 6px 10px; min-width: 120px; border: 1px solid #ddd; border-radius: 4px; margin-right: 5px; }
			.Lukic-empty-state { text-align: center; padding: 40px 20px; background: #f9f9f9; border-radius: 4px; }
			form .form-table th { width: 200px; padding: 20px 10px 20px 0; }
			form .form-table td { padding: 15px 10px; }
			form select, form input[type="text"] { min-width: 300px; padding: 8px 12px; border-radius: 4px; border: 1px solid #ddd; }
			input[type="checkbox"] { margin-right: 8px; }
			.description { margin-top: 4px; color: #666; }
			.Lukic-form { max-width: 800px; margin-top: 20px; }
			.tab-content h2 { margin-top: 0; color: #23282d; font-size: 1.5em; font-weight: 500; }
			#edit-redirect-dialog .ui-dialog-titlebar { background: #00E1AF; color: #fff; border: none; font-weight: 500; }
			#edit-redirect-dialog .ui-dialog-buttonpane { border-top: 1px solid #eee; }
			#edit-redirect-dialog .ui-dialog-buttonset .ui-button { background: #f7f7f7; border: 1px solid #ccc; color: #555; border-radius: 3px; padding: 0.4em 1em; cursor: pointer; }
			#edit-redirect-dialog .ui-dialog-buttonset .ui-button:first-child { background: #00E1AF; border-color: #00E1AF; color: #fff; }
			.alignleft.actions.bulkactions { justify-content: center; display: flex; }
		' );
	}

	/**
	 * Add menu page for redirect management
	 */
	function Lukic_redirect_manager_add_menu() {
		add_submenu_page(
			'lukic-code-snippets',
			__( 'Redirect Manager', 'lukic-code-snippets' ),
			__( 'Redirect Manager', 'lukic-code-snippets' ),
			'manage_options',
			'lukic-redirect-manager',
			'Lukic_redirect_manager_display_page'
		);
	}

	/**
	 * Display the redirect manager admin page
	 */
	function Lukic_redirect_manager_display_page() {

		// Get all redirects and statistics
		global $wpdb;
		$table_name = $wpdb->prefix . 'lukic_redirects';
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$redirects  = $wpdb->get_results( "SELECT * FROM $table_name ORDER BY created_at DESC" );

		// Calculate statistics for header
		$total_redirects  = count( $redirects );
		$active_redirects = count(
			array_filter(
				$redirects,
				function ( $r ) {
					return $r->status == 1;
				}
			)
		);
		$total_hits       = array_sum( array_column( $redirects, 'hits' ) );

		// Prepare stats for header
		$stats = array(
			array(
				'count' => $active_redirects,
				'label' => __( 'Active', 'lukic-code-snippets' ),
			),
			array(
				'count' => $total_redirects,
				'label' => __( 'Total Redirects', 'lukic-code-snippets' ),
			),
			array(
				'count' => number_format( $total_hits ),
				'label' => __( 'Total Hits', 'lukic-code-snippets' ),
			),
		);

		// Display the admin page
		?>
		<div class="wrap Lukic-container Lukic-wrap Lukic-redirect-manager">
			<?php

			// Display header using the loaded component
			Lukic_display_header( __( 'Redirect Manager', 'lukic-code-snippets' ), $stats );
			?>
			
			<div id="Lukic-redirect-tabs" class="Lukic-tabs">
				<nav class="nav-tab-wrapper Lukic-nav-tabs">
					<a href="#tab-redirects" class="nav-tab nav-tab-active"><?php esc_html_e( 'Redirects', 'lukic-code-snippets' ); ?></a>
					<a href="#tab-add-new" class="nav-tab"><?php esc_html_e( 'Add New', 'lukic-code-snippets' ); ?></a>
					<a href="#tab-settings" class="nav-tab"><?php esc_html_e( 'Settings', 'lukic-code-snippets' ); ?></a>
				</nav>
				
				<!-- Redirects Tab -->
				<div id="tab-redirects" class="tab-content">
					<div class="tablenav top">
						<div class="alignleft actions bulkactions">
							<input type="text" id="redirect-search" class="search-input" placeholder="<?php esc_attr_e( 'Search redirects...', 'lukic-code-snippets' ); ?>">
							<select id="redirect-filter-type">
								<option value=""><?php esc_html_e( 'All Redirect Types', 'lukic-code-snippets' ); ?></option>
								<option value="301">301 - <?php esc_html_e( 'Permanent', 'lukic-code-snippets' ); ?></option>
								<option value="302">302 - <?php esc_html_e( 'Temporary', 'lukic-code-snippets' ); ?></option>
								<option value="307">307 - <?php esc_html_e( 'Temporary (Preserves Method)', 'lukic-code-snippets' ); ?></option>
								<option value="308">308 - <?php esc_html_e( 'Permanent (Preserves Method)', 'lukic-code-snippets' ); ?></option>
							</select>
							<select id="redirect-filter-status">
								<option value=""><?php esc_html_e( 'All Statuses', 'lukic-code-snippets' ); ?></option>
								<option value="active"><?php esc_html_e( 'Active', 'lukic-code-snippets' ); ?></option>
								<option value="inactive"><?php esc_html_e( 'Inactive', 'lukic-code-snippets' ); ?></option>
							</select>
							<select id="redirect-filter-pattern">
								<option value=""><?php esc_html_e( 'All URL Types', 'lukic-code-snippets' ); ?></option>
								<option value="exact"><?php esc_html_e( 'Exact Match', 'lukic-code-snippets' ); ?></option>
								<option value="pattern"><?php esc_html_e( 'Pattern Match', 'lukic-code-snippets' ); ?></option>
							</select>
							<button id="redirect-filter-button" class="button" style="background-color: #00E1AF; color: #fff; border-color: #00E1AF;"><?php esc_html_e( 'Filter', 'lukic-code-snippets' ); ?></button>
						</div>
						<br class="clear">
					</div>
					<div class="Lukic-table-container">
						<?php if ( empty( $redirects ) ) : ?>
							<div class="Lukic-empty-state">
								<span class="dashicons dashicons-randomize" style="font-size: 50px; color: #ccc; margin-bottom: 10px;"></span>
								<p><?php esc_html_e( 'No redirects found. Add your first redirect using the "Add New" tab.', 'lukic-code-snippets' ); ?></p>
								<a href="#tab-add-new" class="button button-primary" style="background-color: #00E1AF; border-color: #00E1AF;"><?php esc_html_e( 'Add Your First Redirect', 'lukic-code-snippets' ); ?></a>
							</div>
						<?php else : ?>
							<table class="wp-list-table widefat fixed striped">
								<thead>
									<tr>
										<th><?php esc_html_e( 'Source URL', 'lukic-code-snippets' ); ?></th>
										<th><?php esc_html_e( 'Target URL', 'lukic-code-snippets' ); ?></th>
										<th><?php esc_html_e( 'Type', 'lukic-code-snippets' ); ?></th>
										<th><?php esc_html_e( 'Pattern', 'lukic-code-snippets' ); ?></th>
										<th><?php esc_html_e( 'Status', 'lukic-code-snippets' ); ?></th>
										<th><?php esc_html_e( 'Hits', 'lukic-code-snippets' ); ?></th>
										<th><?php esc_html_e( 'Last Accessed', 'lukic-code-snippets' ); ?></th>
										<th><?php esc_html_e( 'Actions', 'lukic-code-snippets' ); ?></th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ( $redirects as $redirect ) : ?>
										<tr data-id="<?php echo esc_attr( $redirect->id ); ?>">
											<td><?php echo esc_html( $redirect->source_url ); ?></td>
											<td><?php echo esc_html( $redirect->target_url ); ?></td>
											<td><?php echo esc_html( $redirect->redirect_type ); ?></td>
											<td><?php echo wp_kses_post( $redirect->pattern_match ? '<span style="color: #00E1AF;"><span class="dashicons dashicons-yes"></span></span>' : '—' ); ?></td>
											<td><?php echo esc_html( $redirect->status ? __( 'Active', 'lukic-code-snippets' ) : __( 'Inactive', 'lukic-code-snippets' ) ); ?></td>
											<td><?php echo esc_html( $redirect->hits ); ?></td>
											<td><?php echo esc_html( $redirect->last_accessed ? date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $redirect->last_accessed ) ) : __( 'Never', 'lukic-code-snippets' ) ); ?></td>
											<td>
												<a href="#" class="edit-redirect lcs-edit-btn"><?php esc_html_e( 'Edit', 'lukic-code-snippets' ); ?></a> 
												<a href="#" class="delete-redirect lcs-delete-btn"><?php esc_html_e( 'Delete', 'lukic-code-snippets' ); ?></a>
											</td>
										</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						<?php endif; ?>
					</div>
				</div>
				
				<!-- Add New Tab -->
				<div id="tab-add-new" class="tab-content">
					<h2><?php esc_html_e( 'Add New Redirect', 'lukic-code-snippets' ); ?></h2>
					<p><?php esc_html_e( 'Create a new redirect rule to automatically send visitors from one URL to another.', 'lukic-code-snippets' ); ?></p>
					
					<form id="add-redirect-form" method="post" class="Lukic-form">
						<table class="form-table">
							<tr>
								<th scope="row"><label for="source_url"><?php esc_html_e( 'Source URL', 'lukic-code-snippets' ); ?></label></th>
								<td>
									<input type="text" id="source_url" name="source_url" value="" class="regular-text" placeholder="/old-page" required>
									<p class="description"><?php esc_html_e( 'Enter the relative path or full URL. It is usually best without a trailing slash (e.g., /old-page).', 'lukic-code-snippets' ); ?></p>
									<label for="pattern_match" style="margin-top: 10px; display: block;">
										<input type="checkbox" id="pattern_match" name="pattern_match" value="1">
										<?php esc_html_e( 'Use wildcard pattern matching', 'lukic-code-snippets' ); ?>
									</label>
									<p class="description"><?php esc_html_e( 'Enable to use wildcards in the source URL (* matches any characters, ? matches a single character)', 'lukic-code-snippets' ); ?><br>
									<?php esc_html_e( 'Examples: /blog/* will match all URLs starting with /blog/, /product-???/ will match product-123/, etc.', 'lukic-code-snippets' ); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="target_url"><?php esc_html_e( 'Target URL', 'lukic-code-snippets' ); ?></label></th>
								<td>
									<input type="text" id="target_url" name="target_url" value="" class="regular-text" placeholder="/new-page/" required>
									<p class="description"><?php esc_html_e( 'Enter the relative path or full URL. To avoid double redirects, include a trailing slash if your site normally uses them (e.g., /new-page/).', 'lukic-code-snippets' ); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="redirect_type"><?php esc_html_e( 'Redirect Type', 'lukic-code-snippets' ); ?></label></th>
								<td>
									<select id="redirect_type" name="redirect_type">
										<option value="301"><?php esc_html_e( '301 - Permanent Redirect', 'lukic-code-snippets' ); ?></option>
										<option value="302"><?php esc_html_e( '302 - Temporary Redirect', 'lukic-code-snippets' ); ?></option>
										<option value="307"><?php esc_html_e( '307 - Temporary Redirect (Preserves Method)', 'lukic-code-snippets' ); ?></option>
										<option value="308"><?php esc_html_e( '308 - Permanent Redirect (Preserves Method)', 'lukic-code-snippets' ); ?></option>
									</select>
									<p class="description"><?php esc_html_e( 'Select the HTTP status code for the redirect.', 'lukic-code-snippets' ); ?></p>
								</td>
							</tr>
							<tr>
								<th scope="row"><label for="status"><?php esc_html_e( 'Status', 'lukic-code-snippets' ); ?></label></th>
								<td>
									<select id="status" name="status">
										<option value="1"><?php esc_html_e( 'Active', 'lukic-code-snippets' ); ?></option>
										<option value="0"><?php esc_html_e( 'Inactive', 'lukic-code-snippets' ); ?></option>
									</select>
								</td>
							</tr>
						</table>
						
						<input type="hidden" id="redirect_id" name="redirect_id" value="0">
						<?php wp_nonce_field( 'Lukic_redirect_nonce', 'redirect_nonce' ); ?>
						
						<p class="submit">
							<button type="submit" id="submit-redirect" class="button button-primary" style="background-color: #00E1AF; border-color: #00E1AF;">
								
								<?php esc_html_e( 'Save Redirect', 'lukic-code-snippets' ); ?>
							
							</button>
						</p>
					</form>
				</div>
				
				<!-- Settings Tab -->
				<div id="tab-settings" class="tab-content">
					<h2><?php esc_html_e( 'Redirect Manager Settings', 'lukic-code-snippets' ); ?></h2>
					<p><?php esc_html_e( 'Configure how the redirect manager works on your website.', 'lukic-code-snippets' ); ?></p>
					
					<form id="redirect-settings-form" method="post" class="Lukic-form">
						<table class="form-table">
							<tr>
								<th scope="row"><?php esc_html_e( 'Track Hits', 'lukic-code-snippets' ); ?></th>
								<td>
									<label for="track_hits">
										<input type="checkbox" id="track_hits" name="track_hits" value="1" <?php checked( get_option( 'Lukic_redirect_track_hits', 1 ) ); ?>>
										<?php esc_html_e( 'Track the number of times each redirect is accessed.', 'lukic-code-snippets' ); ?>
									</label>
								</td>
							</tr>
							<tr>
								<th scope="row"><?php esc_html_e( 'Log Last Access', 'lukic-code-snippets' ); ?></th>
								<td>
									<label for="log_last_access">
										<input type="checkbox" id="log_last_access" name="log_last_access" value="1" <?php checked( get_option( 'Lukic_redirect_log_last_access', 1 ) ); ?>>
										<?php esc_html_e( 'Record the date and time when each redirect was last accessed.', 'lukic-code-snippets' ); ?>
									</label>
								</td>
							</tr>
						</table>
						
						<?php wp_nonce_field( 'Lukic_redirect_settings_nonce', 'redirect_settings_nonce' ); ?>
						
						<p class="submit">
							<button type="submit" id="save-settings" class="button button-primary" style="background-color: #00E1AF; border-color: #00E1AF;">
								<?php esc_html_e( 'Save Settings', 'lukic-code-snippets' ); ?>
							</button>
						</p>
					</form>
				</div>
			</div>
			
			<!-- Edit Dialog (Hidden) -->
			<div id="edit-redirect-dialog" title="<?php esc_html_e( 'Edit Redirect', 'lukic-code-snippets' ); ?>" style="display:none;">
				<form id="edit-redirect-form">
					<table class="form-table">
						<tr>
							<th scope="row"><label for="edit_source_url"><?php esc_html_e( 'Source URL', 'lukic-code-snippets' ); ?></label></th>
							<td>
								<input type="text" id="edit_source_url" name="source_url" class="regular-text" required>
								<p class="description" style="margin-bottom: 10px;"><?php esc_html_e( 'Usually best without a trailing slash (e.g., /old-page).', 'lukic-code-snippets' ); ?></p>
								<label for="edit_pattern_match" style="margin-top: 10px; display: block;">
									<input type="checkbox" id="edit_pattern_match" name="pattern_match" value="1">
									<?php esc_html_e( 'Use wildcard pattern matching', 'lukic-code-snippets' ); ?>
								</label>
								<p class="description"><?php esc_html_e( 'Enable to use wildcards in the source URL (* matches any characters, ? matches a single character)', 'lukic-code-snippets' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="edit_target_url"><?php esc_html_e( 'Target URL', 'lukic-code-snippets' ); ?></label></th>
							<td>
								<input type="text" id="edit_target_url" name="target_url" class="regular-text" required>
								<p class="description"><?php esc_html_e( 'To avoid double redirects, include a trailing slash if your site uses them (e.g., /new-page/).', 'lukic-code-snippets' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="edit_redirect_type"><?php esc_html_e( 'Redirect Type', 'lukic-code-snippets' ); ?></label></th>
							<td>
								<select id="edit_redirect_type" name="redirect_type">
									<option value="301"><?php esc_html_e( '301 - Permanent Redirect', 'lukic-code-snippets' ); ?></option>
									<option value="302"><?php esc_html_e( '302 - Temporary Redirect', 'lukic-code-snippets' ); ?></option>
									<option value="307"><?php esc_html_e( '307 - Temporary Redirect (Preserves Method)', 'lukic-code-snippets' ); ?></option>
									<option value="308"><?php esc_html_e( '308 - Permanent Redirect (Preserves Method)', 'lukic-code-snippets' ); ?></option>
								</select>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="edit_status"><?php esc_html_e( 'Status', 'lukic-code-snippets' ); ?></label></th>
							<td>
								<select id="edit_status" name="status">
									<option value="1"><?php esc_html_e( 'Active', 'lukic-code-snippets' ); ?></option>
									<option value="0"><?php esc_html_e( 'Inactive', 'lukic-code-snippets' ); ?></option>
								</select>
							</td>
						</tr>
					</table>
					<input type="hidden" id="edit_redirect_id" name="redirect_id">
				</form>
			</div>
		</div>
		<?php
		?>
		<?php
	}

	/**
	 * Process redirects on frontend
	 */
	function Lukic_redirect_manager_process_redirects() {
		// Only process on frontend
		if ( is_admin() ) {
			return;
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'lukic_redirects';

		// Get current path
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated
		$current_url = rtrim( wp_parse_url( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ), PHP_URL_PATH ), '/' );

		// First check for exact match redirects
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$redirect = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE source_url = %s AND pattern_match = 0 AND status = 1", $current_url ) );

		// If no exact match, check for wildcard patterns
		if ( ! $redirect ) {
			// Get all active wildcard redirects
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$pattern_redirects = $wpdb->get_results( "SELECT * FROM $table_name WHERE pattern_match = 1 AND status = 1 ORDER BY source_url ASC" );

			if ( $pattern_redirects ) {
				foreach ( $pattern_redirects as $pattern_redirect ) {
					// Convert wildcard pattern to regex
					$pattern = str_replace(
						array( '\*', '\?' ), // Escape literal * and ? first
						array( '___STAR___', '___QUESTION___' ), // Temporarily replace with tokens
						preg_quote( $pattern_redirect->source_url, '/' )
					);

					// Convert wildcard tokens to regex patterns
					$pattern = str_replace(
						array( '___STAR___', '___QUESTION___' ),
						array( '.*', '.' ),
						$pattern
					);

					// Add start/end anchors and perform match
					$pattern = '/^' . $pattern . '$/i';

					if ( preg_match( $pattern, $current_url ) ) {
						$redirect = $pattern_redirect;

						// Support capture groups in the target URL
						if ( strpos( $redirect->target_url, '$' ) !== false && preg_match( $pattern, $current_url, $matches ) ) {
							$target_url = $redirect->target_url;
							// Replace $1, $2, etc. with corresponding capture groups
							for ( $i = 1; $i < count( $matches ); $i++ ) {
								$target_url = str_replace( '$' . $i, $matches[ $i ], $target_url );
							}
							$redirect->target_url = $target_url;
						}

						break;
					}
				}
			}
		}

		if ( $redirect ) {
			// Update hit count if tracking is enabled
			if ( get_option( 'Lukic_redirect_track_hits', 1 ) ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->update(
					$table_name,
					array(
						'hits'          => $redirect->hits + 1,
						'last_accessed' => current_time( 'mysql' ),
					),
					array( 'id' => $redirect->id ),
					array( '%d', '%s' ),
					array( '%d' )
				);
			}

			// Perform redirect
			// phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
			wp_redirect( $redirect->target_url, $redirect->redirect_type );
			exit;
		}
	}

	/**
	 * AJAX handler for saving a redirect
	 */
	function Lukic_redirect_manager_save_redirect() {
		// Check nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'Lukic_redirect_nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed', 'lukic-code-snippets' ) ) );
		}

		// Check permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to perform this action', 'lukic-code-snippets' ) ) );
		}

		// Sanitize input data
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$redirect_id   = isset( $_POST['redirect_id'] ) ? intval( wp_unslash( $_POST['redirect_id'] ) ) : 0;
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$source_url    = isset( $_POST['source_url'] ) ? sanitize_text_field( wp_unslash( $_POST['source_url'] ) ) : '';
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$target_url    = isset( $_POST['target_url'] ) ? sanitize_text_field( wp_unslash( $_POST['target_url'] ) ) : '';
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$redirect_type = isset( $_POST['redirect_type'] ) ? intval( wp_unslash( $_POST['redirect_type'] ) ) : 301;
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$pattern_match = isset( $_POST['pattern_match'] ) ? 1 : 0;
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$status        = isset( $_POST['status'] ) ? intval( wp_unslash( $_POST['status'] ) ) : 1;

		if ( ! in_array( $redirect_type, array( 301, 302, 307, 308 ), true ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid redirect type', 'lukic-code-snippets' ) ) );
		}

		$status = $status === 0 ? 0 : 1;

		// Validate URLs
		if ( empty( $source_url ) || empty( $target_url ) ) {
			wp_send_json_error( array( 'message' => __( 'Source and target URLs are required', 'lukic-code-snippets' ) ) );
		}

		// Format the source URL (ensure it starts with /)
		if ( strpos( $source_url, 'http' ) !== 0 && $source_url[0] !== '/' ) {
			$source_url = '/' . $source_url;
		}

		// Format the target URL
		// If it starts with www., add https:// to make it an absolute URL
		if ( strpos( $target_url, 'www.' ) === 0 ) {
			$target_url = 'https://' . $target_url;
		}
		// For other URLs that are not absolute and don't start with /, add a leading /
		elseif ( strpos( $target_url, 'http' ) !== 0 && strpos( $target_url, '//' ) !== 0 && $target_url[0] !== '/' ) {
			$target_url = '/' . $target_url;
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'lukic_redirects';

		// Check if source URL already exists (for new redirects only)
		if ( $redirect_id === 0 ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
			$existing = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table_name WHERE source_url = %s", $source_url ) );

			if ( $existing ) {
				wp_send_json_error( array( 'message' => __( 'A redirect for this source URL already exists', 'lukic-code-snippets' ) ) );
			}
		}

		// Prepare data
		$data = array(
			'source_url'    => $source_url,
			'target_url'    => $target_url,
			'redirect_type' => $redirect_type,
			'pattern_match' => $pattern_match,
			'status'        => $status,
		);

		$data_format = array( '%s', '%s', '%d', '%d', '%d' );

		// Update or insert
		if ( $redirect_id > 0 ) {
			// Update existing
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$result = $wpdb->update(
				$table_name,
				$data,
				array( 'id' => $redirect_id ),
				$data_format,
				array( '%d' )
			);

			$message = __( 'Redirect updated successfully', 'lukic-code-snippets' );
		} else {
			// Insert new
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$result = $wpdb->insert(
				$table_name,
				$data,
				$data_format
			);

			$redirect_id = $wpdb->insert_id;
			$message     = __( 'Redirect added successfully', 'lukic-code-snippets' );
		}

		if ( $result === false ) {
			wp_send_json_error( array( 'message' => __( 'Error saving redirect', 'lukic-code-snippets' ) ) );
		}

		wp_send_json_success(
			array(
				'message'  => $message,
				'redirect' => array(
					'id'            => $redirect_id,
					'source_url'    => $source_url,
					'target_url'    => $target_url,
					'redirect_type' => $redirect_type,
					'status'        => $status,
					'hits'          => 0,
					'last_accessed' => null,
				),
			)
		);
	}

	/**
	 * AJAX handler for deleting a redirect
	 */
	function Lukic_redirect_manager_delete_redirect() {
		// Check nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'Lukic_redirect_nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed', 'lukic-code-snippets' ) ) );
		}

		// Check permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to perform this action', 'lukic-code-snippets' ) ) );
		}

		// Get redirect ID
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$redirect_id = isset( $_POST['redirect_id'] ) ? intval( wp_unslash( $_POST['redirect_id'] ) ) : 0;

		if ( $redirect_id <= 0 ) {
			wp_send_json_error( array( 'message' => __( 'Invalid redirect ID', 'lukic-code-snippets' ) ) );
		}

		// Delete redirect
		global $wpdb;
		$table_name = $wpdb->prefix . 'lukic_redirects';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->delete(
			$table_name,
			array( 'id' => $redirect_id ),
			array( '%d' )
		);

		if ( $result === false ) {
			wp_send_json_error( array( 'message' => __( 'Error deleting redirect', 'lukic-code-snippets' ) ) );
		}

		wp_send_json_success( array( 'message' => __( 'Redirect deleted successfully', 'lukic-code-snippets' ) ) );
	}

	/**
	 * AJAX handler for saving redirect settings
	 */
	function Lukic_redirect_manager_save_settings() {
		// Check nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'Lukic_redirect_settings_nonce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Security check failed', 'lukic-code-snippets' ) ) );
		}

		// Check permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'You do not have permission to perform this action', 'lukic-code-snippets' ) ) );
		}

		// Save settings
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$track_hits      = isset( $_POST['track_hits'] ) ? absint( wp_unslash( $_POST['track_hits'] ) ) : 0;
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$log_last_access = isset( $_POST['log_last_access'] ) ? absint( wp_unslash( $_POST['log_last_access'] ) ) : 0;

		$track_hits      = $track_hits ? 1 : 0;
		$log_last_access = $log_last_access ? 1 : 0;

		update_option( 'Lukic_redirect_track_hits', $track_hits );
		update_option( 'Lukic_redirect_log_last_access', $log_last_access );

		wp_send_json_success( array( 'message' => __( 'Settings saved successfully', 'lukic-code-snippets' ) ) );
	}
}
