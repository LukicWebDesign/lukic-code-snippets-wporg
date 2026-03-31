<?php
/**
 * Snippet: Custom Database Tables Manager
 * Description: View and manage custom database tables in your WordPress installation
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class Lukic_DB_Tables_Manager {
	/**
	 * Cache of validated schema columns by table name.
	 *
	 * @var array
	 */
	private $table_schema_cache = array();

	/**
	 * Cache of validated table names.
	 *
	 * @var array|null
	 */
	private $validated_tables = null;

	/**
	 * Constructor
	 */
	public function __construct() {
		// Add submenu page under the main plugin menu
		add_action( 'admin_menu', array( $this, 'add_submenu_page' ), 20 );

		// Register AJAX handlers for database operations
		add_action( 'wp_ajax_Lukic_get_table_structure', array( $this, 'ajax_get_table_structure' ) );
		add_action( 'wp_ajax_Lukic_get_table_data', array( $this, 'ajax_get_table_data' ) );
		add_action( 'wp_ajax_Lukic_export_table', array( $this, 'ajax_export_table' ) );
		add_action( 'wp_ajax_Lukic_update_table_row', array( $this, 'ajax_update_table_row' ) );
		add_action( 'wp_ajax_Lukic_get_table_row', array( $this, 'ajax_get_table_row' ) );
		add_action( 'wp_ajax_Lukic_search_table_data', array( $this, 'ajax_search_table_data' ) );

		// Localize scripts for the admin page
		add_action( 'admin_enqueue_scripts', array( $this, 'localize_admin_scripts' ) );
	}

	/**
	 * Add submenu page
	 */
	public function add_submenu_page() {
		add_submenu_page(
			'lukic-code-snippets', // Parent slug
			__( 'DB Tables Manager', 'lukic-code-snippets' ),
			__( 'DB Tables Manager', 'lukic-code-snippets' ),
			'manage_options',
			'lukic-db-tables-manager',
			array( $this, 'display_admin_page' )
		);
	}

	/**
	 * Localize admin scripts with necessary data.
	 */
	public function localize_admin_scripts( $hook ) {

		// Check if we are on the correct page
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['page'] ) || 'lukic-db-tables-manager' !== sanitize_key( wp_unslash( $_GET['page'] ) ) ) {
			return;
		}

		wp_localize_script(
			'Lukic-db-tables',
			'LukicDBManager',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'Lukic_db_tables_nonce' ),
				'strings' => array(
					'loading'       => __( 'Loading...', 'lukic-code-snippets' ),
					'error'         => __( 'Error occurred', 'lukic-code-snippets' ),
					'exportSuccess' => __( 'Export successful!', 'lukic-code-snippets' ),
					'noData'        => __( 'No data found', 'lukic-code-snippets' ),
				),
			)
		);

		wp_add_inline_style( 'Lukic-admin-styles', '
			.Lukic-wrap { max-width: 1200px; }
			.Lukic-stats-container { display: flex; gap: 20px; margin-bottom: 20px; flex-wrap: wrap; }
			.Lukic-stat-box { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); flex: 1; min-width: 200px; display: flex; align-items: center; border-left: 4px solid #00E1AF; }
			.Lukic-stat-icon { font-size: 36px; padding: 15px; background: rgba(0, 225, 175, 0.1); color: #00E1AF; border-radius: 50%; margin-right: 15px; width: 66px; height: 66px; display: flex; align-items: center; justify-content: center; }
			.Lukic-stat-info h3 { margin: 0 0 5px 0; font-size: 14px; color: #666; text-transform: uppercase; letter-spacing: 0.5px; }
			.Lukic-stat-value { font-size: 24px; font-weight: bold; margin: 0; color: #333; }
			.Lukic-card { background: #fff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); padding: 20px; }
			.Lukic-tabs-container { margin-bottom: 20px; }
			.Lukic-tabs-header { display: flex; border-bottom: 1px solid #ccc; margin-bottom: 20px; }
			.Lukic-tab-button { background: none; border: none; padding: 10px 15px; cursor: pointer; border-bottom: 3px solid transparent; margin-bottom: -1px; font-weight: 600; }
			.Lukic-tab-button:focus { outline: none; box-shadow: none; }
			.Lukic-tab-button.active { border-bottom-color: #00E1AF; color: #00E1AF; }
			.Lukic-tab-content { display: none; }
			.Lukic-tab-content.active { display: block; }
			.Lukic-modal { display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.4); }
			.Lukic-modal-content { background-color: #fefefe; margin: 5% auto; padding: 20px; border: 1px solid #888; border-radius: 5px; width: 90%; max-width: 1200px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
			.Lukic-modal-close { color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer; }
			.Lukic-modal-close:hover { color: #00E1AF; }
			.Lukic-loading { text-align: center; padding: 20px; font-style: italic; color: #666; }
			.Lukic-data-controls { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding: 15px; background: #f9f9f9; border: 1px solid #e1e1e1; border-radius: 4px; flex-wrap: wrap; gap: 15px; }
			.Lukic-search-controls { display: flex; align-items: center; gap: 10px; }
			.Lukic-search-controls input { padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; min-width: 250px; font-size: 14px; }
			.Lukic-search-controls .button { background: var(--Lukic-primary, #00E1AF); border-color: var(--Lukic-primary, #00E1AF); color: white; border-radius: 4px; padding: 8px 16px; font-weight: 600; text-shadow: none; box-shadow: none; }
			.Lukic-search-controls .button:hover { background: var(--Lukic-primary-dark, #00c49a); border-color: var(--Lukic-primary-dark, #00c49a); }
			.Lukic-search-controls .clear-search { background: #666; border-color: #666; }
			.Lukic-search-controls .clear-search:hover { background: #555; border-color: #555; }
			.Lukic-data-pagination { display: flex; align-items: center; gap: 10px; }
			.Lukic-data-pagination .button { background: var(--Lukic-primary, #00E1AF); border-color: var(--Lukic-primary, #00E1AF); color: white; border-radius: 4px; padding: 6px 12px; font-weight: 600; text-shadow: none; box-shadow: none; }
			.Lukic-data-pagination .button:hover:not(:disabled) { background: var(--Lukic-primary-dark, #00c49a); border-color: var(--Lukic-primary-dark, #00c49a); }
			.Lukic-data-pagination .button:disabled { background: #ccc; border-color: #ccc; color: #666; cursor: not-allowed; }
			.export-table { background: var(--Lukic-primary, #00E1AF) !important; border-color: var(--Lukic-primary, #00E1AF) !important; color: white !important; border-radius: 4px !important; padding: 8px 16px !important; font-weight: 600 !important; text-shadow: none !important; box-shadow: none !important; }
			.export-table:hover { background: var(--Lukic-primary-dark, #00c49a) !important; border-color: var(--Lukic-primary-dark, #00c49a) !important; }
			.dataTables_length select { min-width: 80px !important; padding: 4px 25px 4px 8px !important; margin: 0 5px !important; border: 1px solid #ddd !important; border-radius: 4px !important; background: white !important; }
			.dataTables_wrapper .dataTables_length, .dataTables_wrapper .dataTables_filter { margin-bottom: 15px; }
			#DataTables_Table_1_wrapper { padding-top: 10px; }
			.view-table { background: var(--Lukic-primary, #00E1AF) !important; border-color: var(--Lukic-primary, #00E1AF) !important; color: white !important; border-radius: 4px !important; padding: 6px; font-weight: 600 !important; text-shadow: none !important; box-shadow: none !important; }
			.view-table:hover { background: var(--Lukic-primary-dark, #00c49a) !important; border-color: var(--Lukic-primary-dark, #00c49a) !important; }
			.Lukic-data-table { width: 100%; border-collapse: collapse; }
			.Lukic-data-table th { background-color: #f5f5f5; font-weight: 600; }
			.Lukic-data-table th, .Lukic-data-table td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
			.Lukic-data-table tr:hover { background-color: #f9f9f9; }
			.Lukic-edit-actions { margin-top: 20px; text-align: right; }
			.Lukic-edit-actions .button { margin-left: 10px; }
			#edit-form-fields { max-height: 400px; overflow-y: auto; }
			.Lukic-field-group { margin-bottom: 15px; }
			.Lukic-field-group label { display: block; font-weight: 600; margin-bottom: 5px; }
			.Lukic-field-group input, .Lukic-field-group textarea { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
			.Lukic-field-group textarea { min-height: 60px; resize: vertical; }
			.Lukic-edit-btn { background: #1e3a8a !important; border-color: #1e3a8a !important; color: white !important; border-radius: 4px !important; padding: 4px 10px !important; font-size: 12px !important; font-weight: 600 !important; text-shadow: none !important; box-shadow: none !important; margin-left: 5px; }
			.Lukic-edit-btn:hover { background: #1e40af !important; border-color: #1e40af !important; }
			.Lukic-edit-actions .button-primary { background: var(--Lukic-primary, #00E1AF) !important; border-color: var(--Lukic-primary, #00E1AF) !important; color: white !important; border-radius: 4px !important; padding: 8px 16px !important; font-weight: 600 !important; text-shadow: none !important; box-shadow: none !important; }
			.Lukic-edit-actions .button-primary:hover { background: var(--Lukic-primary-dark, #00c49a) !important; border-color: var(--Lukic-primary-dark, #00c49a) !important; }
			.Lukic-edit-cancel { background: #666 !important; border-color: #666 !important; color: white !important; border-radius: 4px !important; padding: 8px 16px !important; font-weight: 600 !important; text-shadow: none !important; box-shadow: none !important; }
			.Lukic-edit-cancel:hover { background: #555 !important; border-color: #555 !important; }
			mark { background-color: #ffeb3b; padding: 1px 2px; border-radius: 2px; }
		' );
	}

	/**
	 * Display the admin page
	 */
	public function display_admin_page() {
		// Get tables information
		$tables_info = $this->get_tables_info();

		// Include the header partial
		// Header component is already loaded in main plugin file

		// Prepare stats for header
		$stats = array(
			array(
				'count' => count( $tables_info['custom'] ),
				'label' => __( 'Custom Tables', 'lukic-code-snippets' ),
			),
			array(
				'count' => count( $tables_info['wordpress'] ),
				'label' => __( 'WP Core Tables', 'lukic-code-snippets' ),
			),
		);

		?>
		<div class="wrap Lukic-settings-wrap">
			<?php Lukic_display_header( __( 'Database Tables Manager', 'lukic-code-snippets' ), $stats ); ?>
			
			<div class="Lukic-settings-intro">
				<p><?php esc_html_e( 'View and manage custom database tables created by plugins and themes.', 'lukic-code-snippets' ); ?></p>
			</div>
			
			<div class="Lukic-settings-container">
				<div class="Lukic-settings-main">
					<div class="Lukic-tab-container">
						<div class="Lukic-tab-nav">
							<button class="Lukic-tab-button active" data-tab="custom-tables"><?php esc_html_e( 'Custom Tables', 'lukic-code-snippets' ); ?></button>
							<button class="Lukic-tab-button" data-tab="wp-tables"><?php esc_html_e( 'WordPress Tables', 'lukic-code-snippets' ); ?></button>
							<button class="Lukic-tab-button" data-tab="all-tables"><?php esc_html_e( 'All Tables', 'lukic-code-snippets' ); ?></button>
						</div>
						
						<div class="Lukic-tab-content active" id="custom-tables">
							<?php $this->render_tables_list( $tables_info['custom'] ); ?>
						</div>
						
						<div class="Lukic-tab-content" id="wp-tables">
							<?php $this->render_tables_list( $tables_info['wordpress'] ); ?>
						</div>
						
						<div class="Lukic-tab-content" id="all-tables">
							<?php $this->render_tables_list( $tables_info['all'] ); ?>
						</div>
					</div>
				</div>
			</div>
			
			<!-- Modal for table details -->
			<div id="Lukic-table-modal" class="Lukic-modal">
				<div class="Lukic-modal-content">
					<span class="Lukic-modal-close">&times;</span>
					<h2 id="Lukic-table-name"></h2>
					
					<div class="Lukic-tab-container">
						<div class="Lukic-tab-nav">
							<button class="Lukic-tab-button active" data-tab="structure"><?php esc_html_e( 'Structure', 'lukic-code-snippets' ); ?></button>
							<button class="Lukic-tab-button" data-tab="data"><?php esc_html_e( 'Data', 'lukic-code-snippets' ); ?></button>
						</div>
						
						<div class="Lukic-tab-content active" id="structure">
							<div id="structure-content">
								<div class="Lukic-loading"><?php esc_html_e( 'Loading structure...', 'lukic-code-snippets' ); ?></div>
							</div>
						</div>
						
						<div class="Lukic-tab-content" id="data">
							<div class="Lukic-data-controls">
								<div class="Lukic-search-controls">
									<input type="text" id="table-search" placeholder="<?php esc_attr_e( 'Search in table...', 'lukic-code-snippets' ); ?>" />
									<button class="button search-table"><?php esc_html_e( 'Search', 'lukic-code-snippets' ); ?></button>
									<button class="button clear-search" style="display: none;"><?php esc_html_e( 'Clear', 'lukic-code-snippets' ); ?></button>
								</div>
								<div class="Lukic-data-pagination">
									<button class="button prev-page" disabled><?php esc_html_e( 'Previous', 'lukic-code-snippets' ); ?></button>
									<span class="pagination-info"></span>
									<button class="button next-page"><?php esc_html_e( 'Next', 'lukic-code-snippets' ); ?></button>
								</div>
								<button class="button export-table"><?php esc_html_e( 'Export to CSV', 'lukic-code-snippets' ); ?></button>
							</div>
							<div id="data-content">
								<div class="Lukic-loading"><?php esc_html_e( 'Loading data...', 'lukic-code-snippets' ); ?></div>
							</div>
						</div>
					</div>
				</div>
			</div>
			
			<!-- Modal for editing table rows -->
			<div id="Lukic-edit-modal" class="Lukic-modal">
				<div class="Lukic-modal-content">
					<span class="Lukic-modal-close Lukic-edit-close">&times;</span>
					<h2><?php esc_html_e( 'Edit Row', 'lukic-code-snippets' ); ?></h2>
					<form id="Lukic-edit-form">
						<div id="edit-form-fields">
							<!-- Form fields will be populated dynamically -->
						</div>
						<div class="Lukic-edit-actions">
							<button type="submit" class="button button-primary"><?php esc_html_e( 'Update Row', 'lukic-code-snippets' ); ?></button>
							<button type="button" class="button Lukic-edit-cancel"><?php esc_html_e( 'Cancel', 'lukic-code-snippets' ); ?></button>
						</div>
					</form>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render tables list
	 */
	private function render_tables_list( $tables ) {
		if ( empty( $tables ) ) {
			echo '<p>' . esc_html__( 'No tables found.', 'lukic-code-snippets' ) . '</p>';
			return;
		}

		?>
		<table class="wp-list-table widefat fixed striped Lukic-tables-list">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Table Name', 'lukic-code-snippets' ); ?></th>
					<th><?php esc_html_e( 'Engine', 'lukic-code-snippets' ); ?></th>
					<th><?php esc_html_e( 'Rows', 'lukic-code-snippets' ); ?></th>
					<th><?php esc_html_e( 'Size', 'lukic-code-snippets' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'lukic-code-snippets' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $tables as $table ) : ?>
				<tr>
					<td><?php echo esc_html( $table['name'] ); ?></td>
					<td><?php echo esc_html( $table['engine'] ); ?></td>
					<td><?php echo esc_html( number_format( $table['rows'] ) ); ?></td>
					<td><?php echo esc_html( $table['size'] ); ?></td>
					<td>
						<button class="button view-table" data-table="<?php echo esc_attr( $table['name'] ); ?>">
							<?php esc_html_e( 'View Details', 'lukic-code-snippets' ); ?>
						</button>
					</td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Get information about all database tables
	 */
	private function get_tables_info() {
		global $wpdb;

		// Get all tables
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$tables = $wpdb->get_results( 'SHOW TABLE STATUS', ARRAY_A );

		if ( empty( $tables ) ) {
			return array(
				'all'       => array(),
				'wordpress' => array(),
				'custom'    => array(),
			);
		}

		// WordPress core tables (using prefix)
		$wp_core_tables = array(
			$wpdb->prefix . 'commentmeta',
			$wpdb->prefix . 'comments',
			$wpdb->prefix . 'links',
			$wpdb->prefix . 'options',
			$wpdb->prefix . 'postmeta',
			$wpdb->prefix . 'posts',
			$wpdb->prefix . 'termmeta',
			$wpdb->prefix . 'terms',
			$wpdb->prefix . 'term_relationships',
			$wpdb->prefix . 'term_taxonomy',
			$wpdb->prefix . 'usermeta',
			$wpdb->prefix . 'users',
		);

		// For multisite installations
		if ( is_multisite() ) {
			$wp_core_tables = array_merge(
				$wp_core_tables,
				array(
					$wpdb->prefix . 'blogs',
					$wpdb->prefix . 'blog_versions',
					$wpdb->prefix . 'signups',
					$wpdb->prefix . 'site',
					$wpdb->prefix . 'sitemeta',
					$wpdb->prefix . 'registration_log',
				)
			);
		}

		$all_tables       = array();
		$wordpress_tables = array();
		$custom_tables    = array();

		foreach ( $tables as $table ) {
			$table_info = array(
				'name'   => isset( $table['Name'] ) ? sanitize_text_field( $table['Name'] ) : '',
				'engine' => isset( $table['Engine'] ) ? sanitize_text_field( $table['Engine'] ) : '',
				'rows'   => isset( $table['Rows'] ) ? (int) $table['Rows'] : 0,
				'size'   => $this->format_size( ( isset( $table['Data_length'] ) ? (float) $table['Data_length'] : 0 ) + ( isset( $table['Index_length'] ) ? (float) $table['Index_length'] : 0 ) ),
			);

			$all_tables[] = $table_info;

			if ( in_array( $table['Name'], $wp_core_tables ) ) {
				$wordpress_tables[] = $table_info;
			} else {
				$custom_tables[] = $table_info;
			}
		}

		return array(
			'all'       => $all_tables,
			'wordpress' => $wordpress_tables,
			'custom'    => $custom_tables,
		);
	}

	/**
	 * Format byte size to human-readable format
	 */
	private function format_size( $bytes ) {
		$units = array( 'B', 'KB', 'MB', 'GB', 'TB' );

		$bytes = max( (float) $bytes, 0 );
		$pow   = floor( ( $bytes ? log( $bytes ) : 0 ) / log( 1024 ) );
		$pow   = min( $pow, count( $units ) - 1 );

		$bytes /= pow( 1024, $pow );

		return round( $bytes, 2 ) . ' ' . $units[ $pow ];
	}

	/**
	 * AJAX handler for getting table structure
	 */
	public function ajax_get_table_structure() {
		// Security check
		check_ajax_referer( 'Lukic_db_tables_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'You do not have sufficient permissions.', 'lukic-code-snippets' ) );
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$table   = isset( $_POST['table'] ) ? sanitize_text_field( wp_unslash( $_POST['table'] ) ) : '';
		$table   = $this->validate_table_name( $table );
		$columns = $table ? $this->get_safe_table_columns( $table ) : array();
		if ( ! $table ) {
			wp_send_json_error( __( 'Invalid table specified.', 'lukic-code-snippets' ) );
		}

		// Use the cached validated schema for the structure response.
		if ( empty( $columns ) ) {
			wp_send_json_error( __( 'Could not retrieve table structure.', 'lukic-code-snippets' ) );
		}

		ob_start();
		?>
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Field', 'lukic-code-snippets' ); ?></th>
					<th><?php esc_html_e( 'Type', 'lukic-code-snippets' ); ?></th>
					<th><?php esc_html_e( 'Null', 'lukic-code-snippets' ); ?></th>
					<th><?php esc_html_e( 'Key', 'lukic-code-snippets' ); ?></th>
					<th><?php esc_html_e( 'Default', 'lukic-code-snippets' ); ?></th>
					<th><?php esc_html_e( 'Extra', 'lukic-code-snippets' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $columns as $column ) : ?>
				<tr>
					<td><?php echo esc_html( $column->Field ); ?></td>
					<td><?php echo esc_html( $column->Type ); ?></td>
					<td><?php echo esc_html( $column->Null ); ?></td>
					<td><?php echo esc_html( $column->Key ); ?></td>
					<td><?php echo is_null( $column->Default ) ? wp_kses_post( '<em>NULL</em>' ) : esc_html( $column->Default ); ?></td>
					<td><?php echo esc_html( $column->Extra ); ?></td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
		$html = ob_get_clean();

		wp_send_json_success(
			array(
				'html' => $html,
			)
		);
	}

	/**
	 * AJAX handler for getting table data
	 */
	public function ajax_get_table_data() {
		// Security check
		check_ajax_referer( 'Lukic_db_tables_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'You do not have sufficient permissions.', 'lukic-code-snippets' ) );
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$table    = isset( $_POST['table'] ) ? sanitize_text_field( wp_unslash( $_POST['table'] ) ) : '';
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$page     = isset( $_POST['page'] ) ? max( 1, intval( wp_unslash( $_POST['page'] ) ) ) : 1;
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$per_page = isset( $_POST['per_page'] ) ? max( 1, min( 200, intval( wp_unslash( $_POST['per_page'] ) ) ) ) : 20;
		global $wpdb;
		$table = $this->validate_table_name( $table );
		if ( ! $table ) {
			wp_send_json_error( __( 'Invalid table specified.', 'lukic-code-snippets' ) );
		}
		$columns        = $this->get_safe_table_columns( $table );
		$safe_table     = $this->quote_identifier( $table );
		$select_columns = $this->get_safe_select_columns_sql( $columns );
		$primary_column = $this->get_primary_key_column( $columns );
		if ( empty( $columns ) || '' === $select_columns ) {
			wp_send_json_error( __( 'Could not retrieve table structure.', 'lukic-code-snippets' ) );
		}

		// Get total rows count — $table is validated by validate_table_name().
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$total_rows = $wpdb->get_var( "SELECT COUNT(*) FROM {$safe_table}" );

		// Calculate offset
		$offset = ( $page - 1 ) * $per_page;

		// Get data with pagination — use prepare() for LIMIT params.
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT {$select_columns} FROM {$safe_table} LIMIT %d, %d", $offset, $per_page ), ARRAY_A );

		if ( $rows === null ) {
			wp_send_json_error( __( 'Error retrieving data from table.', 'lukic-code-snippets' ) . ' ' . $wpdb->last_error );
		}

		if ( empty( $rows ) ) {
			ob_start();
			?>
			<div class="notice notice-info">
				<p><?php esc_html_e( 'This table does not contain any data.', 'lukic-code-snippets' ); ?></p>
			</div>
			<?php
			$html = ob_get_clean();

			wp_send_json_success(
				array(
					'html'    => $html,
					'total'   => 0,
					'pages'   => 0,
					'current' => 1,
				)
			);
		}

		// Calculate total pages
		$total_pages = (int) ceil( $total_rows / $per_page );

		// Resolve the primary key from the validated schema cache.
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$primary_key = $primary_column ? $primary_column->Field : null;

		ob_start();
		?>
		<table class="wp-list-table widefat fixed striped Lukic-data-table">
			<thead>
				<tr>
					<?php foreach ( array_keys( $rows[0] ) as $column ) : ?>
					<th><?php echo esc_html( $column ); ?></th>
					<?php endforeach; ?>
					<th><?php esc_html_e( 'Actions', 'lukic-code-snippets' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $rows as $row_index => $row ) : ?>
				<tr>
					<?php foreach ( $row as $column => $value ) : ?>
					<td>
						<?php
						if ( is_null( $value ) ) {
							echo wp_kses_post( '<em>NULL</em>' );
						} else {
							$string_value = is_scalar( $value ) ? (string) $value : maybe_serialize( $value );
							if ( ! mb_check_encoding( $string_value, 'UTF-8' ) ) {
								$string_value = wp_check_invalid_utf8( $string_value );
							}
							echo esc_html( mb_substr( $string_value, 0, 100, 'UTF-8' ) );
						}
						?>
					</td>
					<?php endforeach; ?>
					<td>
						<?php if ( $primary_key && isset( $row[ $primary_key ] ) ) : ?>
						<button class="button Lukic-edit-btn edit-row" 
								data-table="<?php echo esc_attr( $table ); ?>" 
								data-pk="<?php echo esc_attr( $primary_key ); ?>" 
								data-pk-value="<?php echo esc_attr( $row[ $primary_key ] ); ?>">
							<?php esc_html_e( 'Edit', 'lukic-code-snippets' ); ?>
						</button>
						<?php else : ?>
						<em><?php esc_html_e( 'No PK', 'lukic-code-snippets' ); ?></em>
						<?php endif; ?>
					</td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
		$html = ob_get_clean();

		wp_send_json_success(
			array(
				'html'    => $html,
				'total'   => $total_rows,
				'pages'   => $total_pages,
				'current' => $page,
			)
		);
	}

	/**
	 * AJAX handler for exporting table data to CSV
	 */
	public function ajax_export_table() {
		// Security check
		check_ajax_referer( 'Lukic_db_tables_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'You do not have sufficient permissions.', 'lukic-code-snippets' ) );
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$table = isset( $_POST['table'] ) ? sanitize_text_field( wp_unslash( $_POST['table'] ) ) : '';
		global $wpdb;
		$table = $this->validate_table_name( $table );
		if ( ! $table ) {
			wp_send_json_error( __( 'Invalid table specified.', 'lukic-code-snippets' ) );
		}
		$columns        = $this->get_safe_table_columns( $table );
		$safe_table     = $this->quote_identifier( $table );
		$select_columns = $this->get_safe_select_columns_sql( $columns );

		if ( empty( $columns ) || '' === $select_columns ) {
			wp_send_json_error( __( 'Could not retrieve table structure.', 'lukic-code-snippets' ) );
		}

		// Get all data from the table — $table is validated by validate_table_name().
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$rows = $wpdb->get_results( "SELECT {$select_columns} FROM {$safe_table}", ARRAY_A );

		if ( empty( $rows ) ) {
			wp_send_json_error( __( 'No data to export.', 'lukic-code-snippets' ) );
		}

		// Generate CSV content
		$csv = array();

		// Add headers
		$headers = array_keys( $rows[0] );
		$csv[]   = '"' . implode( '","', $headers ) . '"';

		// Add rows
		foreach ( $rows as $row ) {
			$csv_row = array();
			foreach ( $row as $value ) {
				$value     = is_null( $value ) ? '' : ( is_scalar( $value ) ? (string) $value : maybe_serialize( $value ) );
				$csv_row[] = '"' . str_replace( '"', '""', wp_check_invalid_utf8( $value ) ) . '"';
			}
			$csv[] = implode( ',', $csv_row );
		}

		$csv_content = implode( "\n", $csv );

		wp_send_json_success(
			array(
				'filename' => sanitize_file_name( $table ) . '-export-' . gmdate( 'Y-m-d' ) . '.csv',
				'content'  => $csv_content,
			)
		);
	}

	/**
	 * AJAX handler for getting a single table row for editing
	 */
	public function ajax_get_table_row() {
		// Security check
		check_ajax_referer( 'Lukic_db_tables_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'You do not have sufficient permissions.', 'lukic-code-snippets' ) );
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$table         = isset( $_POST['table'] ) ? sanitize_text_field( wp_unslash( $_POST['table'] ) ) : '';
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$primary_key   = isset( $_POST['primary_key'] ) ? sanitize_text_field( wp_unslash( $_POST['primary_key'] ) ) : '';
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$primary_value = isset( $_POST['primary_value'] ) ? sanitize_text_field( wp_unslash( $_POST['primary_value'] ) ) : '';
		global $wpdb;
		$table = $this->validate_table_name( $table );
		if ( ! $table ) {
			wp_send_json_error( __( 'Invalid table specified.', 'lukic-code-snippets' ) );
		}
		$columns        = $this->get_safe_table_columns( $table );
		$safe_table     = $this->quote_identifier( $table );
		$select_columns = $this->get_safe_select_columns_sql( $columns );

		$primary_key = $this->validate_column_name( $primary_key );
		if ( ! $primary_key ) {
			wp_send_json_error( __( 'Invalid primary key.', 'lukic-code-snippets' ) );
		}

		if ( empty( $primary_value ) ) {
			wp_send_json_error( __( 'Missing required parameters.', 'lukic-code-snippets' ) );
		}

		// Confirm the requested primary key exists in the validated schema cache.
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
		if ( empty( $columns ) || '' === $select_columns || ! isset( $columns[ $primary_key ] ) ) {
			wp_send_json_error( __( 'Invalid primary key.', 'lukic-code-snippets' ) );
		}
		$primary_column = $columns[ $primary_key ];
		$primary_value  = $this->normalize_column_value( $primary_value, $primary_column );
		$placeholder    = $this->get_column_placeholder( $primary_column->Type );

		if ( null === $primary_value ) {
			wp_send_json_error( __( 'Missing required parameters.', 'lukic-code-snippets' ) );
		}

		// Get the specific row
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT {$select_columns} FROM {$safe_table} WHERE " . $this->quote_identifier( $primary_key ) . " = {$placeholder}",
				$primary_value
			),
			ARRAY_A
		);

		if ( ! $row ) {
			wp_send_json_error( __( 'Row not found.', 'lukic-code-snippets' ) );
		}

		wp_send_json_success(
			array(
				'row'         => $row,
				'columns'     => array_values( $columns ),
				'primary_key' => $primary_key,
			)
		);
	}

	/**
	 * AJAX handler for updating a table row
	 */
	public function ajax_update_table_row() {
		// Security check
		check_ajax_referer( 'Lukic_db_tables_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'You do not have sufficient permissions.', 'lukic-code-snippets' ) );
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$table         = isset( $_POST['table'] ) ? sanitize_text_field( wp_unslash( $_POST['table'] ) ) : '';
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$primary_key   = isset( $_POST['primary_key'] ) ? sanitize_text_field( wp_unslash( $_POST['primary_key'] ) ) : '';
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$primary_value = isset( $_POST['primary_value'] ) ? sanitize_text_field( wp_unslash( $_POST['primary_value'] ) ) : '';
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$row_data      = isset( $_POST['row_data'] ) ? wp_unslash( (array) $_POST['row_data'] ) : array();
		global $wpdb;
		$table = $this->validate_table_name( $table );
		if ( ! $table ) {
			wp_send_json_error( __( 'Invalid table specified.', 'lukic-code-snippets' ) );
		}
		$columns = $this->get_safe_table_columns( $table );

		$primary_key = $this->validate_column_name( $primary_key );
		if ( ! $primary_key || empty( $primary_value ) || empty( $row_data ) ) {
			wp_send_json_error( __( 'Missing required parameters.', 'lukic-code-snippets' ) );
		}
		if ( empty( $columns ) || ! isset( $columns[ $primary_key ] ) ) {
			wp_send_json_error( __( 'Invalid primary key.', 'lukic-code-snippets' ) );
		}
		$primary_column = $columns[ $primary_key ];
		$primary_value  = $this->normalize_column_value( $primary_value, $primary_column );
		if ( null === $primary_value ) {
			wp_send_json_error( __( 'Missing required parameters.', 'lukic-code-snippets' ) );
		}

		// Normalize row data against the validated schema.
		$update_data   = array();
		$update_format = array();
		foreach ( $row_data as $column => $value ) {
			$column = $this->validate_column_name( $column );
			if ( ! $column || ! isset( $columns[ $column ] ) ) {
				continue;
			}
			if ( $column === $primary_key ) {
				continue;
			}

			if ( ! is_scalar( $value ) && null !== $value ) {
				continue;
			}

			$update_data[ $column ] = $this->normalize_column_value( $value, $columns[ $column ] );
			$update_format[]        = $this->get_column_placeholder( $columns[ $column ]->Type );
		}

		if ( empty( $update_data ) ) {
			wp_send_json_error( __( 'No data to update.', 'lukic-code-snippets' ) );
		}

		// Update the row
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$result = $wpdb->update(
			$table,
			$update_data,
			array( $primary_key => $primary_value ),
			$update_format,
			array( $this->get_column_placeholder( $primary_column->Type ) )
		);

		if ( $result === false ) {
			wp_send_json_error( __( 'Failed to update row.', 'lukic-code-snippets' ) . ' ' . $wpdb->last_error );
		}

		wp_send_json_success( __( 'Row updated successfully.', 'lukic-code-snippets' ) );
	}

	/**
	 * AJAX handler for searching table data
	 */
	public function ajax_search_table_data() {

		// Security check
		check_ajax_referer( 'Lukic_db_tables_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'You do not have sufficient permissions.', 'lukic-code-snippets' ) );
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$table       = isset( $_POST['table'] ) ? sanitize_text_field( wp_unslash( $_POST['table'] ) ) : '';
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$search_term = isset( $_POST['search_term'] ) ? sanitize_text_field( wp_unslash( $_POST['search_term'] ) ) : '';
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$page        = isset( $_POST['page'] ) ? max( 1, intval( wp_unslash( $_POST['page'] ) ) ) : 1;
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$per_page    = isset( $_POST['per_page'] ) ? max( 1, min( 200, intval( wp_unslash( $_POST['per_page'] ) ) ) ) : 20;
		global $wpdb;
		$table = $this->validate_table_name( $table );
		if ( ! $table ) {
			wp_send_json_error( __( 'Invalid table specified.', 'lukic-code-snippets' ) );
		}

		// Load the validated schema once for the search query path.
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$columns        = $this->get_safe_table_columns( $table );
		$safe_table     = $this->quote_identifier( $table );
		$select_columns = $this->get_safe_select_columns_sql( $columns );
		$primary_column = $this->get_primary_key_column( $columns );
		if ( empty( $columns ) || '' === $select_columns ) {
			wp_send_json_error( __( 'Could not retrieve table structure.', 'lukic-code-snippets' ) );
		}

		$where_data    = $this->build_safe_where_clause( $columns, $search_term );
		$where_clause  = $where_data['sql'];
		$search_values = $where_data['params'];

		// Get total rows count for search
		$count_query = "SELECT COUNT(*) FROM {$safe_table} {$where_clause}";
		if ( ! empty( $search_values ) ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$total_rows = $wpdb->get_var( $wpdb->prepare( $count_query, $search_values ) );
		} else {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$total_rows = $wpdb->get_var( $count_query );
		}

		// Calculate offset
		$offset = ( $page - 1 ) * $per_page;

		// Get data with search and pagination — use prepare() for LIMIT params.
		$data_query = "SELECT {$select_columns} FROM {$safe_table} {$where_clause} LIMIT %d, %d";
		if ( ! empty( $search_values ) ) {
			$search_values[] = $offset;
			$search_values[] = $per_page;
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$rows = $wpdb->get_results( $wpdb->prepare( $data_query, $search_values ), ARRAY_A );
		} else {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$rows = $wpdb->get_results( $wpdb->prepare( $data_query, $offset, $per_page ), ARRAY_A );
		}

		if ( $rows === null ) {
			wp_send_json_error( __( 'Error retrieving data from table.', 'lukic-code-snippets' ) . ' ' . $wpdb->last_error );
		}

		if ( empty( $rows ) ) {
			ob_start();
			?>
			<div class="notice notice-info">
				<p>
				<?php
				if ( ! empty( $search_term ) ) {
					/* translators: %s: The search term entered by the user */
					echo esc_html( sprintf( __( 'No results found for "%s".', 'lukic-code-snippets' ), $search_term ) );
				} else {
					esc_html_e( 'This table does not contain any data.', 'lukic-code-snippets' );
				}
				?>
				</p>
			</div>
			<?php
			$html = ob_get_clean();

			wp_send_json_success(
				array(
					'html'        => $html,
					'total'       => 0,
					'pages'       => 0,
					'current'     => 1,
					'search_term' => $search_term,
				)
			);
		}

		$primary_key = $primary_column ? $primary_column->Field : null;

		// Calculate total pages
		$total_pages = (int) ceil( $total_rows / $per_page );

		ob_start();
		?>
		<table class="wp-list-table widefat fixed striped Lukic-data-table">
			<thead>
				<tr>
					<?php foreach ( array_keys( $rows[0] ) as $column ) : ?>
					<th><?php echo esc_html( $column ); ?></th>
					<?php endforeach; ?>
					<th><?php esc_html_e( 'Actions', 'lukic-code-snippets' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $rows as $row_index => $row ) : ?>
				<tr>
					<?php foreach ( $row as $column => $value ) : ?>
					<td>
						<?php
						if ( is_null( $value ) ) {
							$display_value = wp_kses_post( '<em>NULL</em>' );
						} else {
							$string_value = is_scalar( $value ) ? (string) $value : maybe_serialize( $value );
							if ( ! mb_check_encoding( $string_value, 'UTF-8' ) ) {
								$string_value = wp_check_invalid_utf8( $string_value );
							}
							$display_value = esc_html( mb_substr( $string_value, 0, 100, 'UTF-8' ) );
						}

						// Highlight search term if present
						if ( ! empty( $search_term ) && ! is_null( $value ) ) {
							$display_value = str_ireplace(
								esc_html( $search_term ),
								'<mark>' . esc_html( $search_term ) . '</mark>',
								$display_value
							);
						}

						echo wp_kses_post( $display_value );
						?>
					</td>
					<?php endforeach; ?>
					<td>
						<?php if ( $primary_key && isset( $row[ $primary_key ] ) ) : ?>
						<button class="button Lukic-edit-btn edit-row" 
								data-table="<?php echo esc_attr( $table ); ?>" 
								data-pk="<?php echo esc_attr( $primary_key ); ?>" 
								data-pk-value="<?php echo esc_attr( $row[ $primary_key ] ); ?>">
							<?php esc_html_e( 'Edit', 'lukic-code-snippets' ); ?>
						</button>
						<?php else : ?>
						<em><?php esc_html_e( 'No PK', 'lukic-code-snippets' ); ?></em>
						<?php endif; ?>
					</td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
		$html = ob_get_clean();

		wp_send_json_success(
			array(
				'html'        => $html,
				'total'       => $total_rows,
				'pages'       => $total_pages,
				'current'     => $page,
				'search_term' => $search_term,
			)
		);
	}

	/**
	 * Get all validated table names available to the current database.
	 *
	 * @return array
	 */
	private function get_validated_table_names() {
		if ( is_array( $this->validated_tables ) ) {
			return $this->validated_tables;
		}

		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$table_names = $wpdb->get_col( 'SHOW TABLES' );

		$this->validated_tables = array();

		foreach ( (array) $table_names as $table_name ) {
			$table_name = is_string( $table_name ) ? trim( $table_name ) : '';
			if ( '' !== $table_name && preg_match( '/^[A-Za-z0-9_]+$/', $table_name ) ) {
				$this->validated_tables[] = $table_name;
			}
		}

		return $this->validated_tables;
	}

	/**
	 * Return cached table schema with validated identifiers only.
	 *
	 * @param string $table Validated table name.
	 * @return array
	 */
	private function get_safe_table_columns( $table ) {
		$table = $this->validate_table_name( $table );
		if ( ! $table ) {
			return array();
		}

		if ( isset( $this->table_schema_cache[ $table ] ) ) {
			return $this->table_schema_cache[ $table ];
		}

		global $wpdb;
		$safe_table = $this->quote_identifier( $table );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
		$columns = $wpdb->get_results( "DESCRIBE {$safe_table}" );

		if ( empty( $columns ) ) {
			$this->table_schema_cache[ $table ] = array();
			return array();
		}

		$safe_columns = array();
		foreach ( $columns as $column ) {
			$field = isset( $column->Field ) ? $this->validate_column_name( $column->Field ) : false;
			if ( ! $field ) {
				continue;
			}

			$safe_columns[ $field ] = (object) array(
				'Field'   => $field,
				'Type'    => isset( $column->Type ) ? sanitize_text_field( (string) $column->Type ) : '',
				'Null'    => isset( $column->Null ) ? sanitize_text_field( (string) $column->Null ) : '',
				'Key'     => isset( $column->Key ) ? sanitize_text_field( (string) $column->Key ) : '',
				'Default' => property_exists( $column, 'Default' ) ? $column->Default : null,
				'Extra'   => isset( $column->Extra ) ? sanitize_text_field( (string) $column->Extra ) : '',
			);
		}

		$this->table_schema_cache[ $table ] = $safe_columns;

		return $safe_columns;
	}

	/**
	 * Quote a validated SQL identifier.
	 *
	 * @param string $identifier Validated identifier.
	 * @return string
	 */
	private function quote_identifier( $identifier ) {
		return '`' . $identifier . '`';
	}

	/**
	 * Build a comma-separated list of validated column identifiers.
	 *
	 * @param array $columns Validated schema columns.
	 * @return string
	 */
	private function get_safe_select_columns_sql( $columns ) {
		$identifiers = array();

		foreach ( $columns as $column ) {
			$identifiers[] = $this->quote_identifier( $column->Field );
		}

		return implode( ', ', $identifiers );
	}

	/**
	 * Find the primary key column in a validated schema list.
	 *
	 * @param array $columns Validated schema columns.
	 * @return object|null
	 */
	private function get_primary_key_column( $columns ) {
		foreach ( $columns as $column ) {
			if ( isset( $column->Key ) && 'PRI' === $column->Key ) {
				return $column;
			}
		}

		return null;
	}

	/**
	 * Return columns that are safe and practical to search with LIKE.
	 *
	 * @param array $columns Validated schema columns.
	 * @return array
	 */
	private function get_searchable_columns( $columns ) {
		$searchable = array();

		foreach ( $columns as $column ) {
			$type = strtolower( (string) $column->Type );

			if ( false !== strpos( $type, 'blob' ) || false !== strpos( $type, 'binary' ) || false !== strpos( $type, 'geometry' ) ) {
				continue;
			}

			$searchable[] = $column;
		}

		return $searchable;
	}

	/**
	 * Build a safe WHERE clause for text search using validated columns only.
	 *
	 * @param array  $columns     Validated schema columns.
	 * @param string $search_term Search term.
	 * @return array
	 */
	private function build_safe_where_clause( $columns, $search_term ) {
		$search_term = is_string( $search_term ) ? $search_term : '';
		if ( '' === $search_term ) {
			return array(
				'sql'    => '',
				'params' => array(),
			);
		}

		global $wpdb;

		$fragments    = array();
		$search_value = '%' . $wpdb->esc_like( $search_term ) . '%';
		$params       = array();

		foreach ( $this->get_searchable_columns( $columns ) as $column ) {
			$fragments[] = 'CAST(' . $this->quote_identifier( $column->Field ) . ' AS CHAR) LIKE %s';
			$params[]    = $search_value;
		}

		if ( empty( $fragments ) ) {
			return array(
				'sql'    => '',
				'params' => array(),
			);
		}

		return array(
			'sql'    => 'WHERE (' . implode( ' OR ', $fragments ) . ')',
			'params' => $params,
		);
	}

	/**
	 * Get the safest available placeholder for a schema column type.
	 *
	 * @param string $column_type Raw schema type.
	 * @return string
	 */
	private function get_column_placeholder( $column_type ) {
		$column_type = strtolower( (string) $column_type );

		if ( preg_match( '/(?:tinyint|smallint|mediumint|int|bigint|bit|serial)/', $column_type ) ) {
			return '%d';
		}

		if ( preg_match( '/(?:decimal|numeric|float|double|real)/', $column_type ) ) {
			return '%f';
		}

		return '%s';
	}

	/**
	 * Normalize a value for a validated schema column.
	 *
	 * @param mixed  $value  Raw value.
	 * @param object $column Validated schema column object.
	 * @return mixed
	 */
	private function normalize_column_value( $value, $column ) {
		if ( null === $value || 'NULL' === $value || '' === $value ) {
			return null;
		}

		$placeholder = $this->get_column_placeholder( $column->Type );

		if ( '%d' === $placeholder ) {
			return (int) $value;
		}

		if ( '%f' === $placeholder ) {
			return (float) $value;
		}

		return sanitize_textarea_field( (string) $value );
	}

	/**
	 * Validate provided table name against allowed characters and actual schema.
	 *
	 * @param string $table Raw table name.
	 * @return string|false
	 */
	private function validate_table_name( $table ) {
		$table = is_string( $table ) ? trim( $table ) : '';
		if ( '' === $table || ! preg_match( '/^[A-Za-z0-9_]+$/', $table ) ) {
			return false;
		}

		return in_array( $table, $this->get_validated_table_names(), true ) ? $table : false;
	}

	/**
	 * Validate a column/identifier name.
	 *
	 * @param string $identifier Raw identifier.
	 * @return string|false
	 */
	private function validate_column_name( $identifier ) {
		$identifier = is_string( $identifier ) ? trim( $identifier ) : '';
		if ( '' === $identifier || ! preg_match( '/^[A-Za-z0-9_]+$/', $identifier ) ) {
			return false;
		}

		return $identifier;
	}
}

// Initialize the DB Tables Manager
new Lukic_DB_Tables_Manager();
