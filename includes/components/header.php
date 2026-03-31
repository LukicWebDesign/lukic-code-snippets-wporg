<?php
/**
 * Displays the common header for all Lukic Code Snippets admin pages
 *
 * @package Lukic_Snippet_Codes
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Display the plugin header with optional stats
 *
 * @param string $page_title The title of the current page
 * @param array  $stats      Array of statistics to display
 */
function Lukic_display_header( $page_title = '', $stats = array() ) {
	?>
	<div class="wpl-code-snippets-header">
		<div class="wpl-code-snippets-header__content"> 
			<div class="wpl-code-snippets-header__brand">
				<h2><span style="color: var(--Lukic-primary, #00E1AF);">Lukic</span> Code Snippets</h2>
				<?php if ( ! empty( $page_title ) ) : ?>
					<p><?php echo esc_html( $page_title ); ?></p>
				<?php endif; ?>
			</div>

			<?php if ( ! empty( $stats ) ) : ?>
				<div class="wpl-code-snippets-header__stats">
					<?php foreach ( $stats as $stat ) : ?>
						<div class="wpl-code-snippets-header__stats-item">
							<?php if ( is_array( $stat ) && isset( $stat['count'], $stat['label'] ) ) : ?>
								<div class="wpl-code-snippets-header__stats-item-count"><?php echo esc_html( $stat['count'] ); ?></div>
								<div class="wpl-code-snippets-header__stats-item-label"><?php echo esc_html( $stat['label'] ); ?></div>
							<?php elseif ( is_string( $stat ) ) : ?>
								<div class="wpl-code-snippets-header__stats-item-label"><?php echo esc_html( $stat ); ?></div>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
	</div>
	<?php
}

/**
 * Get common statistics for the header
 *
 * @return array Array of statistics
 */
function Lukic_get_header_stats() {
	$options = get_option( 'Lukic_snippet_codes_options', array() );

	// Count active snippets
	$active_count = count(
		array_filter(
			$options,
			function ( $value ) {
				return $value == 1;
			}
		)
	);

	// Total available snippets
	$total_count = Lukic_Snippet_Codes::get_total_snippets_count();

	return array(
		array(
			'count' => $active_count,
			'label' => __( 'Active', 'lukic-code-snippets' ),
		),
		array(
			'count' => $total_count,
			'label' => __( 'Total', 'lukic-code-snippets' ),
		),
	);
}
