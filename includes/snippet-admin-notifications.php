<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Snippet: Admin Notifications Manager
 * Description: Manage, organize, and control WordPress admin notifications.
 */

if ( ! function_exists( 'Lukic_admin_notifications_manager' ) ) {
	/**
	 * Initialize the Admin Notifications Manager.
	 */
	function Lukic_admin_notifications_manager() {
		// Hide plugin update notifications for non-admin users.
		if ( ! current_user_can( 'update_plugins' ) ) {
			remove_action( 'admin_notices', 'update_nag', 3 );
			remove_action( 'network_admin_notices', 'update_nag', 3 );
		}

		add_action( 'admin_enqueue_scripts', 'Lukic_admin_notifications_scripts' );
		add_action( 'admin_footer', 'Lukic_render_notifications_container', 999 );
	}
	add_action( 'admin_init', 'Lukic_admin_notifications_manager' );

	/**
	 * Enqueue styles and scripts for the notifications manager.
	 */
	function Lukic_admin_notifications_scripts() {
		wp_enqueue_script( 'jquery' );

		wp_add_inline_style(
			'Lukic-admin-styles',
			'
			#Lukic-admin-notifications-container { display: none; background: #fff; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04); margin: 10px 20px 0 2px; padding: 10px; position: relative; border-radius: 4px; }
			.Lukic-notifications-header { display: flex; align-items: center; border-bottom: 1px solid #e2e4e7; padding-bottom: 8px; margin-bottom: 10px; gap: 10px; }
			.Lukic-notifications-header h3 { margin: 0; flex-grow: 1; }
			.Lukic-notifications-count { background: #ca4a1f; color: #fff; border-radius: 50%; width: 20px; height: 20px; display: inline-flex; align-items: center; justify-content: center; font-size: 11px; }
			.Lukic-dismiss-all { background: none; border: 0; cursor: pointer; color: #0073aa; font-size: 12px; padding: 0; }
			.Lukic-dismiss-all:hover { text-decoration: underline; }
			.Lukic-notice-group { margin-bottom: 15px; }
			.Lukic-notice-group h4 { margin: 0 0 5px 0; padding-bottom: 5px; border-bottom: 1px solid #f1f1f1; }
			'
		);

		$labels = array(
			'error'   => __( 'Errors', 'lukic-code-snippets' ),
			'warning' => __( 'Warnings', 'lukic-code-snippets' ),
			'success' => __( 'Success', 'lukic-code-snippets' ),
			'info'    => __( 'Information', 'lukic-code-snippets' ),
			'other'   => __( 'Other Notifications', 'lukic-code-snippets' ),
		);

		$script = '
			jQuery(function($) {
				const $container = $("#Lukic-admin-notifications-container");
				const $content = $("#Lukic-admin-notifications-content");
				const labels = ' . wp_json_encode( $labels ) . ';

				if (!$container.length || !$content.length) {
					return;
				}

				const $notices = $(".notice, .update-nag, div.updated, div.error").filter(function() {
					const $notice = $(this);

					if ($notice.closest("#Lukic-admin-notifications-container").length) {
						return false;
					}

					if ($notice.hasClass("Lukic-auto-save-notice")) {
						return false;
					}

					if ($notice.closest(".inline-edit-row, .quick-edit-row, .media-modal").length) {
						return false;
					}

					return true;
				});

				if (!$notices.length) {
					$container.remove();
					return;
				}

				const groups = {
					error: [],
					warning: [],
					success: [],
					info: [],
					other: []
				};

				function getNoticeType($notice) {
					if ($notice.hasClass("notice-error") || $notice.hasClass("error")) {
						return "error";
					}

					if ($notice.hasClass("notice-warning") || $notice.hasClass("update-nag")) {
						return "warning";
					}

					if ($notice.hasClass("notice-success") || $notice.hasClass("updated")) {
						return "success";
					}

					if ($notice.hasClass("notice-info")) {
						return "info";
					}

					return "other";
				}

				$notices.each(function() {
					const $notice = $(this);
					groups[getNoticeType($notice)].push($notice.detach().show());
				});

				$.each(groups, function(type, notices) {
					if (!notices.length) {
						return;
					}

					const $group = $("<div/>", {
						class: "Lukic-notice-group Lukic-notice-group-" + type
					});

					$("<h4/>").text(labels[type] + " (" + notices.length + ")").appendTo($group);

					notices.forEach(function($notice) {
						$group.append($notice);
					});

					$content.append($group);
				});

				$("#Lukic-admin-notifications-count").text($notices.length);
				$container.show();

				$("#Lukic-admin-dismiss-all").on("click", function(event) {
					event.preventDefault();
					$content.find(".notice, .update-nag, div.updated, div.error").slideUp(150, function() {
						$(this).remove();
					});
					$container.remove();
				});
			});
		';

		wp_add_inline_script( 'jquery', $script );
	}

	/**
	 * Render the notifications container used by the client-side organizer.
	 */
	function Lukic_render_notifications_container() {
		?>
		<div id="Lukic-admin-notifications-container">
			<div class="Lukic-notifications-header">
				<h3><?php esc_html_e( 'Notifications', 'lukic-code-snippets' ); ?></h3>
				<span id="Lukic-admin-notifications-count" class="Lukic-notifications-count">0</span>
				<button type="button" id="Lukic-admin-dismiss-all" class="Lukic-dismiss-all">
					<?php esc_html_e( 'Dismiss All', 'lukic-code-snippets' ); ?>
				</button>
			</div>
			<div id="Lukic-admin-notifications-content" class="Lukic-notifications-content"></div>
		</div>
		<?php
	}
}
