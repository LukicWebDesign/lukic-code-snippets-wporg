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
	add_action( 'admin_head', 'Lukic_notices_style' );
	add_action( 'admin_footer', 'Lukic_notices_script' );
}
add_action( 'admin_init', 'Lukic_hide_admin_notices', 1 );

/**
 * Add CSS to hide notices on the page but keep them visible in the panel
 */
function Lukic_notices_style() {
	?>
	<style type="text/css">
		/* Class to hide original notices after they are processed */
		.lukic-hidden-original {
			display: none !important;
		}

		/* Admin Bar Button */
		#wpadminbar .Lukic-toggle-notices {
			cursor: pointer;
		}

		/* Panel Styles */
		#Lukic-notices-panel {
			position: fixed;
			top: 32px;
			right: 0;
			width: 450px;
			max-width: 100%;
			height: calc(100vh - 32px);
			background: #f1f1f1;
			z-index: 99999;
			box-shadow: -3px 0 15px rgba(0, 0, 0, 0.15);
			display: flex;
			flex-direction: column;
			transform: translateX(100%);
			transition: transform 0.3s ease;
		}

		#Lukic-notices-panel.is-open {
			transform: translateX(0);
		}
		
		.Lukic-notices-header {
			padding: 15px 20px;
			background: #fff;
			border-bottom: 1px solid #ddd;
			display: flex;
			justify-content: space-between;
			align-items: center;
			flex-shrink: 0;
		}
		
		.Lukic-notices-header h2 {
			margin: 0;
			font-size: 16px;
			font-weight: 600;
			color: #1d2327;
		}
		
		.Lukic-close-notices {
			background: none;
			border: none;
			cursor: pointer;
			font-size: 20px;
			padding: 0;
			color: #787c82;
			line-height: 1;
		}

		.Lukic-close-notices:hover {
			color: #d63638;
		}
		
		.Lukic-notices-content {
			padding: 20px;
			overflow-y: auto;
			flex-grow: 1;
		}

		/* Wrapper for notices in the panel to isolate styles */
		.lukic-notice-wrapper {
			background: #fff !important;
			border-left: 4px solid #72aee6 !important;
			box-shadow: 0 1px 1px rgba(0,0,0,0.04) !important;
			margin: 0 0 10px 0 !important;
			padding: 10px 12px !important;
			display: block !important;
			min-height: 20px;
		}
		
		.lukic-notice-wrapper.success { border-left-color: #46b450 !important; }
		.lukic-notice-wrapper.warning { border-left-color: #f0b849 !important; }
		.lukic-notice-wrapper.error { border-left-color: #dc3232 !important; }
		
		/* Reset styles for content inside wrapper */
		.lukic-notice-wrapper > * {
			margin: 0 !important;
			padding: 0 !important;
			background: transparent !important;
			border: none !important;
			box-shadow: none !important;
			color: #3c434a !important;
			position: static !important;
			width: auto !important;
			height: auto !important;
			display: block !important;
			opacity: 1 !important;
			visibility: visible !important;
			font-size: 13px !important;
			line-height: 1.5 !important;
		}
		
		.lukic-notice-wrapper p {
			margin-bottom: 0.5em !important;
		}
		.lukic-notice-wrapper p:last-child {
			margin-bottom: 0 !important;
		}
		
		/* Hide dismiss buttons */
		.lukic-notice-wrapper .notice-dismiss {
			display: none !important;
		}

		.Lukic-no-notices {
			text-align: center;
			color: #646970;
			margin-top: 50px;
		}

		.Lukic-no-notices .dashicons {
			font-size: 48px;
			width: 48px;
			height: 48px;
			margin-bottom: 10px;
			color: #a7aaad;
		}

		/* Admin Bar Button */
		#wpadminbar .Lukic-toggle-notices {
			cursor: pointer;
		}
		
		#wpadminbar .Lukic-notice-count {
			display: inline-block;
			background: #ca4a1f;
			color: #fff;
			font-size: 10px;
			line-height: 14px;
			padding: 0 5px;
			border-radius: 10px;
			margin-left: 5px;
		}
		
		#wpadminbar .Lukic-notice-count.zero {
			background: #46b450;
			display: none; /* Hide if zero */
		}

		@media screen and (max-width: 782px) {
			#Lukic-notices-panel {
				top: 46px;
				height: calc(100vh - 46px);
				width: 100%;
			}
		}
	</style>
	<?php
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
			<div class="Lukic-no-notices" style="display: none;">
				<span class="dashicons dashicons-yes-alt"></span>
				<p><?php esc_html_e( 'No notices found.', 'lukic-code-snippets' ); ?></p>
			</div>
		</div>
	</div>
	<?php
}

/**
 * Add JavaScript for the notices panel
 */
function Lukic_notices_script() {
	?>
	<script type="text/javascript">
		jQuery(document).ready(function($) {
			var $panel = $('#Lukic-notices-panel');
			var $container = $('#Lukic-notices-container');
			var $badge = $('.Lukic-notice-count');
			var $noNotices = $('.Lukic-no-notices');
			var count = 0;
			var observer; // Define observer variable
			
			if ($container.length === 0) {
				return;
			}

			// Selectors for standard WordPress notices
			var noticeSelectors = [
				'.notice-error',
				'.notice-warning',
				'.notice-success',
				'.notice-info',
				'.update-nag',
				'.updated',
				'.error',
				'.is-dismissible',
				'.notice',
				'#message'
			];
			
			var selectorString = noticeSelectors.join(', ');

			// Function to process notices
			function processNotices() {
				// Disconnect observer to prevent infinite loops while we modify DOM
				if (observer) {
					observer.disconnect();
				}

				// Find notices that are NOT inside our panel already
				var $notices = $(selectorString).not('#Lukic-notices-panel *');

				if ($notices.length > 0) {
					$notices.each(function() {
						var $notice = $(this);
						
						// Skip empty notices
						if ($.trim($notice.text()) === '') {
							return;
						}
						
						// Determine type for styling
						var type = 'info';
						if ($notice.hasClass('notice-success') || $notice.hasClass('updated')) type = 'success';
						if ($notice.hasClass('notice-warning')) type = 'warning';
						if ($notice.hasClass('notice-error') || $notice.hasClass('error')) type = 'error';

						// Create a clean wrapper
						var $wrapper = $('<div class="lukic-notice-wrapper ' + type + '"></div>');
						
						// Detach the notice from the page (removes it completely)
						var $content = $notice.detach();
						
						// Clean up the content
						$content.removeClass('is-dismissible notice updated error update-nag notice-error notice-warning notice-success notice-info');
						$content.removeAttr('style'); // Remove any inline styles that might hide it
						
						// Put content in wrapper
						$wrapper.append($content);
						
						// Append wrapper to panel
						$container.append($wrapper);
						
						count++;
					});
					
					updateBadge();
				}

				// Reconnect observer
				if (observer) {
					observer.observe(document.body, { childList: true, subtree: true });
				}
			}
			
			function updateBadge() {
				$badge.text(count);
				if (count > 0) {
					$badge.removeClass('zero');
					$noNotices.hide();
				} else {
					$badge.addClass('zero');
					$noNotices.show();
				}
			}

			// Initial run
			processNotices();
			
			// Observe for new notices (AJAX, etc.)
			observer = new MutationObserver(function(mutations) {
				var shouldProcess = false;
				mutations.forEach(function(mutation) {
					// Ignore mutations inside our own panel
					if ($(mutation.target).closest('#Lukic-notices-panel').length > 0) {
						return;
					}
					if (mutation.addedNodes.length) {
						shouldProcess = true;
					}
				});
				if (shouldProcess) {
					processNotices();
				}
			});
			
			// Start observing the body for added nodes
			observer.observe(document.body, { childList: true, subtree: true });

			// Toggle panel
			$('.Lukic-toggle-notices').on('click', function(e) {
				e.preventDefault();
				$panel.toggleClass('is-open');
			});

			// Close panel
			$('.Lukic-close-notices').on('click', function() {
				$panel.removeClass('is-open');
			});

			// Close on click outside
			$(document).on('click', function(e) {
				if (!$(e.target).closest('#Lukic-notices-panel, .Lukic-toggle-notices').length) {
					$panel.removeClass('is-open');
				}
			});
		});
	</script>
	<?php
}
