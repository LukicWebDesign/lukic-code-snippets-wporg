<?php
/**
 * Hide Admin Notices
 *
 * Hide unnecessary admin notices, allowing for a more focused and efficient admin experience.
 * Creates a clean interface by moving all notifications to a dedicated "Notices" menu.
 *
 * @package Lukic_Snippet_Codes
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Hide all admin notices from the WordPress dashboard
 */
function Lukic_hide_admin_notices() {
	// Check if we should bypass the notice hiding
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$show_notices = isset( $_GET['show_admin_notices'] ) ? sanitize_text_field( wp_unslash( $_GET['show_admin_notices'] ) ) : '';
	if ( $show_notices === '1' ) {
		return;
	}

	if ( ! is_admin() ) {
		return;
	}

	// Only do this for users who can at least edit posts
	if ( ! current_user_can( 'edit_posts' ) ) {
		return;
	}

	// Add a toggle notice button to the admin bar
	add_action( 'admin_bar_menu', 'Lukic_add_notices_button', 999 );

	// Add notice panel to admin footer
	add_action( 'admin_footer', 'Lukic_add_notices_panel' );

	// Add necessary styles and scripts
	add_action( 'admin_enqueue_scripts', 'Lukic_enqueue_notices_assets' );
}
add_action( 'admin_init', 'Lukic_hide_admin_notices', 1 );

/**
 * Enqueue CSS and JS for the notices panel.
 */
function Lukic_enqueue_notices_assets() {
	wp_enqueue_style(
		'Lukic-hide-admin-notices',
		plugin_dir_url( __DIR__ ) . 'assets/css/hide-admin-notices.css',
		array(),
		Lukic_SNIPPET_CODES_VERSION
	);
	wp_enqueue_script(
		'Lukic-hide-admin-notices',
		plugin_dir_url( __DIR__ ) . 'assets/js/hide-admin-notices.js',
		array( 'jquery' ),
		Lukic_SNIPPET_CODES_VERSION,
		true
	);
}

/**
 * Add a "Notices" button to the admin bar
 */
function Lukic_add_notices_button( $admin_bar ) {
	$admin_bar->add_menu(
		array(
			'id'    => 'Lukic-notices',
			'title' => sprintf( 'Notices <span class="Lukic-notice-count zero">0</span>' ),
			'href'  => '#',
			'meta'  => array(
				'title' => __( 'View Hidden Notices', 'lukic-code-snippets' ),
				'class' => 'Lukic-toggle-notices',
			),
		)
	);
}

/**
 * Add hidden notices panel to admin footer
 */
function Lukic_add_notices_panel() {
	?>
	<div id="Lukic-notices-panel">
		<div class="Lukic-notices-header">
			<h2><?php esc_html_e( 'Admin Notices', 'lukic-code-snippets' ); ?></h2>
			<button type="button" class="Lukic-close-notices">
				<span class="dashicons dashicons-no-alt"></span>
			</button>
		</div>
		<div class="Lukic-notices-content" id="Lukic-notices-container">
			<!-- Notices will be moved here via JS -->
			<div class="Lukic-no-notices">
				<span class="dashicons dashicons-yes-alt"></span>
				<p><?php esc_html_e( 'No notices found.', 'lukic-code-snippets' ); ?></p>
			</div>
		</div>
	</div>
	<?php
}
