<?php
/**
 * Last Login User - shows when each user last logged in
 *
 * This snippet adds a "Last login" column to the WordPress admin users list
 * showing when each user was last logged in or "No data" if never logged in.
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class Lukic_Last_Login
 */
class Lukic_Last_Login {

	/**
	 * Constructor
	 */
	public function __construct() {
		// Track user login time
		add_action( 'wp_login', array( $this, 'update_login_timestamp' ), 10, 2 );

		// Add the last login column to users list
		add_filter( 'manage_users_columns', array( $this, 'add_last_login_column' ) );
		add_filter( 'manage_users_custom_column', array( $this, 'display_last_login_column_content' ), 10, 3 );

		// Make the column sortable
		add_filter( 'manage_users_sortable_columns', array( $this, 'make_last_login_column_sortable' ) );

		// Add the sorting functionality
		add_action( 'pre_get_users', array( $this, 'sort_by_last_login' ) );
	}

	/**
	 * Update the login timestamp when a user logs in
	 *
	 * @param string  $user_login The user's login name
	 * @param WP_User $user The user object
	 */
	public function update_login_timestamp( $user_login, $user ) {
		update_user_meta( $user->ID, 'Lukic_last_login', current_time( 'timestamp' ) );
	}

	/**
	 * Add the last login column to the users list table
	 *
	 * @param array $columns The existing columns
	 * @return array Modified columns
	 */
	public function add_last_login_column( $columns ) {
		$columns['last_login'] = __( 'Last login', 'lukic-code-snippets' );
		return $columns;
	}

	/**
	 * Display the last login time in the column
	 *
	 * @param string $output The column output
	 * @param string $column_name The column name
	 * @param int    $user_id The user ID
	 * @return string The column content
	 */
	public function display_last_login_column_content( $output, $column_name, $user_id ) {
		if ( 'last_login' === $column_name ) {
			$last_login = get_user_meta( $user_id, 'Lukic_last_login', true );

			if ( ! empty( $last_login ) ) {
				// Format the timestamp into a human-readable format
				$format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
				$output = date_i18n( $format, $last_login );

				// Add a title with the relative time (e.g., "3 days ago")
				$time_diff = human_time_diff( $last_login, current_time( 'timestamp' ) );
				/* translators: %s: Time difference (e.g., "3 days", "2 hours") */
				$output = '<span title="' . sprintf( __( '%s ago', 'lukic-code-snippets' ), $time_diff ) . '">' . $output . '</span>';
			} else {
				$output = '<span class="Lukic-no-login-data">' . __( 'No data', 'lukic-code-snippets' ) . '</span>';
			}
		}

		return $output;
	}

	/**
	 * Make the last login column sortable
	 *
	 * @param array $columns The sortable columns
	 * @return array Modified sortable columns
	 */
	public function make_last_login_column_sortable( $columns ) {
		$columns['last_login'] = 'last_login';
		return $columns;
	}

	/**
	 * Add sorting functionality for the last login column
	 *
	 * @param WP_User_Query $user_query The WP_User_Query object
	 */
	public function sort_by_last_login( $user_query ) {
		// Only run on the admin users list page
		if ( ! is_admin() || ! function_exists( 'get_current_screen' ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen || 'users' !== $screen->id ) {
			return;
		}

		// Check if we're sorting by last login
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['orderby'] ) && 'last_login' === sanitize_text_field( wp_unslash( $_GET['orderby'] ) ) ) {
			// Add the meta query to sort by last login
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			$user_query->query_vars['meta_key'] = 'Lukic_last_login';
			$user_query->query_vars['orderby']  = 'meta_value_num';
		}
	}
}

// Initialize the class
new Lukic_Last_Login();
