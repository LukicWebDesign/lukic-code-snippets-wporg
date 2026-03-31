<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Snippet: Admin Notifications Manager
 * Description: Manage, organize, and control WordPress admin notifications
 */

if ( ! function_exists( 'Lukic_admin_notifications_manager' ) ) {
	/**
	 * Initialize the Admin Notifications Manager
	 */
	function Lukic_admin_notifications_manager() {
		// Hide plugin update notifications for non-admin users
		if ( ! current_user_can( 'update_plugins' ) ) {
			remove_action( 'admin_notices', 'update_nag', 3 );
			remove_action( 'network_admin_notices', 'update_nag', 3 );
		}

		// Add the notification manager styles and scripts
		add_action( 'admin_enqueue_scripts', 'Lukic_admin_notifications_scripts' );

		// Filter the admin notices to group and organize them
		add_action( 'admin_notices', 'Lukic_group_admin_notices', 0 );
		add_action( 'admin_footer', 'Lukic_render_notifications_container', 999 );

		// Add AJAX handler for dismissing notices
		add_action( 'wp_ajax_Lukic_dismiss_admin_notice', 'Lukic_dismiss_admin_notice_callback' );
	}
	add_action( 'admin_init', 'Lukic_admin_notifications_manager' );

	/**
	 * Enqueue scripts and styles for the notifications manager
	 */
	function Lukic_admin_notifications_scripts() {
		// Register and enqueue CSS
		wp_register_style(
			'Lukic-admin-notifications',
			plugin_dir_url( __DIR__ ) . 'assets/css/admin-notifications.css',
			array(),
			Lukic_SNIPPET_CODES_VERSION
		);
		wp_enqueue_style( 'Lukic-admin-notifications' );

		// Register and enqueue JavaScript
		wp_register_script(
			'Lukic-admin-notifications',
			plugin_dir_url( __DIR__ ) . 'assets/js/admin-notifications.js',
			array( 'jquery' ),
			Lukic_SNIPPET_CODES_VERSION,
			true
		);

		// Pass data to script
		wp_localize_script(
			'Lukic-admin-notifications',
			'LukicNotifications',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'Lukic_notifications_nonce' ),
			)
		);

		wp_enqueue_script( 'Lukic-admin-notifications' );

		// Add inline CSS via WordPress API
		wp_add_inline_style( 'Lukic-admin-notifications', '
			#Lukic-admin-notifications-container { background: #fff; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04); margin: 10px 20px 0 2px; padding: 10px; position: relative; border-radius: 4px; }
			.Lukic-notifications-header { display: flex; align-items: center; border-bottom: 1px solid #e2e4e7; padding-bottom: 8px; margin-bottom: 10px; }
			.Lukic-notifications-header h3 { margin: 0; flex-grow: 1; }
			.Lukic-notifications-count { background: #ca4a1f; color: #fff; border-radius: 50%; width: 20px; height: 20px; display: inline-flex; align-items: center; justify-content: center; font-size: 11px; margin-right: 10px; }
			.Lukic-dismiss-all { cursor: pointer; color: #0073aa; font-size: 12px; }
			.Lukic-dismiss-all:hover { text-decoration: underline; }
			.Lukic-notice-group { margin-bottom: 15px; }
			.Lukic-notice-group h4 { margin: 0 0 5px 0; padding-bottom: 5px; border-bottom: 1px solid #f1f1f1; }
			.Lukic-notice-dismiss { float: right; color: #aaa; cursor: pointer; margin-left: 5px; }
			.Lukic-notice-dismiss:hover { color: #dc3232; }
			.notice-debug-info { color: #888; font-size: 10px; margin-top: 5px; opacity: 0.7; }
		' );
	}

	/**
	 * Start output buffering to capture admin notices
	 */
	function Lukic_group_admin_notices() {
		// Start output buffering to capture notices
		ob_start();
	}

	/**
	 * End output buffering and render organized notices
	 */
	function Lukic_render_notifications_container() {
		// Only clean the buffer if one is active (ob_start was called)
		if ( ob_get_level() < 1 ) {
			return;
		}

		// Get the buffered notices
		$notices = ob_get_clean();

		// If there are no notices, just return
		if ( empty( $notices ) ) {
			return;
		}

		// Create a container for the notices
		echo '<div id="Lukic-admin-notifications-container">';
		echo '<div class="Lukic-notifications-header">';
		echo '<h3>' . esc_html__( 'Notifications', 'lukic-code-snippets' ) . '</h3>';
		echo '<span class="Lukic-notifications-count">' . esc_html( Lukic_count_notifications( $notices ) ) . '</span>';
		echo '<span class="Lukic-dismiss-all">' . esc_html__( 'Dismiss All', 'lukic-code-snippets' ) . '</span>';
		echo '</div>';
		echo '<div class="Lukic-notifications-content">';

		// Add dismiss buttons to all notices
		$notices = Lukic_add_dismiss_buttons( $notices );

		// Group notifications by type (the output is already properly formed HTML from inner notices)
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo Lukic_organize_notifications( $notices );

		echo '</div>'; // .Lukic-notifications-content
		echo '</div>'; // #Lukic-admin-notifications-container

	}

	/**
	 * Count the number of notifications
	 */
	function Lukic_count_notifications( $notices ) {
		// Simple count based on div.notice elements
		return substr_count( $notices, 'div class="notice' );
	}

	/**
	 * Add dismiss buttons to each notice
	 */
	function Lukic_add_dismiss_buttons( $notices ) {
		return preg_replace_callback(
			'/<div\s[^>]*class="[^"]*notice[^"]*"[^>]*>.*?<\/div>/s',
			function ( $matches ) {
				return preg_replace(
					'/<\/div>\s*$/',
					'<span class="Lukic-notice-dismiss dashicons dashicons-dismiss" title="' . esc_attr__( 'Dismiss', 'lukic-code-snippets' ) . '"></span></div>',
					$matches[0]
				);
			},
			$notices
		);
	}

	/**
	 * Allowlist for buffered admin notice HTML.
	 *
	 * @return array
	 */
	function Lukic_admin_notifications_allowed_html() {
		return array(
			'a'      => array(
				'class'  => true,
				'href'   => true,
				'id'     => true,
				'rel'    => true,
				'target' => true,
				'title'  => true,
			),
			'b'      => array(),
			'br'     => array(),
			'button' => array(
				'class'    => true,
				'disabled' => true,
				'id'       => true,
				'name'     => true,
				'type'     => true,
				'value'    => true,
			),
			'code'   => array(),
			'div'    => array(
				'aria-hidden'      => true,
				'class'            => true,
				'data-dismissible' => true,
				'data-slug'        => true,
				'id'               => true,
				'role'             => true,
			),
			'em'     => array(),
			'form'   => array(
				'action' => true,
				'class'  => true,
				'id'     => true,
				'method' => true,
			),
			'h1'     => array( 'class' => true ),
			'h2'     => array( 'class' => true ),
			'h3'     => array( 'class' => true ),
			'h4'     => array( 'class' => true ),
			'img'    => array(
				'alt'    => true,
				'class'  => true,
				'height' => true,
				'src'    => true,
				'width'  => true,
			),
			'input'  => array(
				'checked'  => true,
				'class'    => true,
				'disabled' => true,
				'id'       => true,
				'name'     => true,
				'type'     => true,
				'value'    => true,
			),
			'label'  => array(
				'class' => true,
				'for'   => true,
			),
			'li'     => array( 'class' => true ),
			'ol'     => array( 'class' => true ),
			'p'      => array( 'class' => true ),
			'pre'    => array( 'class' => true ),
			'span'   => array(
				'aria-hidden' => true,
				'class'       => true,
				'id'          => true,
				'role'        => true,
				'title'       => true,
			),
			'strong' => array(),
			'table'  => array( 'class' => true ),
			'tbody'  => array(),
			'td'     => array(
				'class' => true,
				'colspan' => true,
			),
			'th'     => array(
				'class'   => true,
				'colspan' => true,
				'scope'   => true,
			),
			'thead'  => array(),
			'tr'     => array( 'class' => true ),
			'ul'     => array( 'class' => true ),
		);
	}

	/**
	 * Organize notifications by type
	 */
	function Lukic_organize_notifications( $notices ) {
		$output = '';

		// Split notices by div.notice tags
		preg_match_all( '/<div\s[^>]*class="[^"]*notice[^"]*"[^>]*>.*?<\/div>/s', $notices, $matches );

		if ( empty( $matches[0] ) ) {
			return $notices; // If no matches, return the original
		}

		// Organize by type (error, warning, success, info)
		$types = array(
			'error'   => array(),
			'warning' => array(),
			'success' => array(),
			'info'    => array(),
			'other'   => array(),
		);

		foreach ( $matches[0] as $notice ) {
			// Add debug class to show notice source
			$source_label = strpos( $notice, 'data-slug' ) !== false
				? __( 'Plugin Update', 'lukic-code-snippets' )
				: ( strpos( $notice, 'settings-error' ) !== false
					? __( 'Settings Page', 'lukic-code-snippets' )
					: ( strpos( $notice, 'update-message' ) !== false
						? __( 'WordPress Update', 'lukic-code-snippets' )
						: __( 'Unknown', 'lukic-code-snippets' ) ) );
			$debug_info   = '<div class="notice-debug-info">[' .
				sprintf(
					/* translators: %s: notice source */
					esc_html__( 'Notice Source: %s', 'lukic-code-snippets' ),
					esc_html( $source_label )
				) .
				']</div>';

			$notice = str_replace( '</div>', $debug_info . '</div>', $notice );
			$notice = wp_kses( $notice, Lukic_admin_notifications_allowed_html() );

			// Improved classification logic
			if ( strpos( $notice, 'notice-error' ) !== false || strpos( $notice, 'error' ) !== false ) {
				$types['error'][] = $notice;
			} elseif ( strpos( $notice, 'notice-warning' ) !== false || strpos( $notice, 'update-nag' ) !== false ) {
				$types['warning'][] = $notice;
			} elseif ( strpos( $notice, 'notice-success' ) !== false || strpos( $notice, 'updated' ) !== false ) {
				$types['success'][] = $notice;
			} elseif ( strpos( $notice, 'notice-info' ) !== false ) {
				$types['info'][] = $notice;
			} else {
				$types['other'][] = $notice;
			}
		}

		// Create groups for each type
		$labels = array(
			'error'   => __( 'Errors', 'lukic-code-snippets' ),
			'warning' => __( 'Warnings', 'lukic-code-snippets' ),
			'success' => __( 'Success', 'lukic-code-snippets' ),
			'info'    => __( 'Information', 'lukic-code-snippets' ),
			'other'   => __( 'Other Notifications', 'lukic-code-snippets' ),
		);

		foreach ( $types as $type => $type_notices ) {
			if ( ! empty( $type_notices ) ) {
				$output .= '<div class="Lukic-notice-group Lukic-notice-group-' . sanitize_html_class( $type ) . '">';
				$output .= '<h4>' . esc_html( $labels[ $type ] ) . ' (' . esc_html( count( $type_notices ) ) . ')</h4>';
				$output .= implode( '', $type_notices );
				$output .= '</div>';
			}
		}

		return $output;
	}

	/**
	 * AJAX callback for dismissing notices
	 */
	function Lukic_dismiss_admin_notice_callback() {
		// Check nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'Lukic_notifications_nonce' ) ) {
			wp_send_json_error( 'Invalid nonce' );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Permission denied' );
		}

		// Process dismiss action
		// In a real implementation, we'd store the dismissed notice ID
		// For this simple version, we just return success
		wp_send_json_success();
	}
}

// Add a debug function to verify the snippet is loaded
if ( ! function_exists( 'Lukic_admin_notifications_debug' ) ) {
	function Lukic_admin_notifications_debug() {
		// Only show for admins
		if ( current_user_can( 'manage_options' ) ) {
			echo '<!-- Lukic Admin Notifications Manager snippet is active -->';
		}
	}
	add_action( 'admin_footer', 'Lukic_admin_notifications_debug' );
}
