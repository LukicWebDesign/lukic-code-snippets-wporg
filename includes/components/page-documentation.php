<?php
/**
 * Documentation Page Component
 *
 * @package Lukic_Snippet_Codes
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Display the documentation page
 */
function Lukic_display_documentation_page() {
	// Get all snippets grouped by category
	$grouped_snippets = Lukic_Snippet_Registry::get_snippets_by_category();
	$categories       = Lukic_Snippet_Registry::get_categories();
	$all_snippets     = Lukic_Snippet_Registry::get_snippets();

	?>
	<div class="wrap Lukic-wrap Lukic-documentation" style="background: #fff; padding: 20px; box-sizing: border-box;">
		<?php
		// Display header
		Lukic_display_header( __( 'Documentation', 'lukic-code-snippets' ), Lukic_get_header_stats() );
		?>

		<div class="Lukic-documentation-controls">
			<input type="text" id="Lukic-doc-search" placeholder="<?php esc_attr_e( 'Search documentation...', 'lukic-code-snippets' ); ?>" class="Lukic-search-input">
		</div>

		<div class="Lukic-documentation-content">
			<?php foreach ( $categories as $category_slug => $category_data ) : ?>
				<?php 
				if ( empty( $grouped_snippets[ $category_slug ] ) ) {
					continue;
				}
				?>
				<div class="Lukic-doc-section" id="cat-<?php echo esc_attr( $category_slug ); ?>">
					<h3 class="Lukic-doc-section-title">
						<span class="dashicons <?php echo esc_attr( $category_data['icon'] ); ?>"></span>
						<?php echo esc_html( $category_data['name'] ); ?>
					</h3>
					
					<div class="Lukic-doc-grid">
						<?php foreach ( $grouped_snippets[ $category_slug ] as $snippet_id => $snippet_basic ) : ?>
							<?php 
							// Get full snippet details
							$snippet = Lukic_Snippet_Registry::get_snippet( $snippet_id );
							?>
							<div class="Lukic-doc-card" data-search="<?php echo esc_attr( strtolower( $snippet['name'] . ' ' . $snippet['description'] . ' ' . implode( ' ', $snippet['tags'] ) ) ); ?>">
								<div class="Lukic-doc-card-header">
									<h3 class="Lukic-doc-card-title"><?php echo esc_html( $snippet['name'] ); ?></h3>
								</div>
								
								<div class="Lukic-doc-card-body">
									<p><?php echo esc_html( ! empty( $snippet['long_description'] ) ? $snippet['long_description'] : $snippet['description'] ); ?></p>
								</div>
								
								<?php if ( ! empty( $snippet['tags'] ) ) : ?>
									<div class="Lukic-doc-card-footer">
										<div class="Lukic-doc-tags">
											<?php foreach ( $snippet['tags'] as $tag ) : ?>
												<span class="Lukic-tag"><?php echo esc_html( $tag ); ?></span>
											<?php endforeach; ?>
										</div>
									</div>
								<?php endif; ?>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>

		<div id="Lukic-doc-no-results" style="display: none; text-align: center; padding: 40px;">
			<p><?php esc_html_e( 'No snippets found matching your search.', 'lukic-code-snippets' ); ?></p>
		</div>
	<?php
}

/**
 * Enqueue scripts and styles for documentation page
 */
function Lukic_enqueue_documentation_scripts( $hook ) {
	if ( strpos( $hook, 'lukic-code-snippets-documentation' ) === false ) {
		return;
	}

	wp_register_style( 'Lukic-doc-styles', false );
	wp_enqueue_style( 'Lukic-doc-styles' );
	wp_add_inline_style( 'Lukic-doc-styles', '
		.Lukic-documentation-controls { margin-bottom: 30px; display: flex; justify-content: flex-end; }
		.Lukic-search-input { width: 100%; max-width: 220px; padding: 10px 15px; font-size: 16px; border: 1px solid #ddd; border-radius: 4px; }
		.Lukic-doc-section { margin-bottom: 40px; }
		.Lukic-doc-section-title { font-size: 1.5em; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; border-bottom: 1px solid #eee; padding-bottom: 10px; }
		.Lukic-doc-grid { display: grid; grid-template-columns: 1fr; gap: 20px; }
		@media (min-width: 768px) { .Lukic-doc-grid { grid-template-columns: repeat(2, 1fr); } }
		.Lukic-doc-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 6px; padding: 20px; display: flex; flex-direction: column; transition: box-shadow 0.2s ease; height: 100%; box-sizing: border-box; }
		.Lukic-doc-card:hover { box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06); }
		.Lukic-doc-card-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px; gap: 10px; }
		.Lukic-doc-card-title { margin: 0; font-size: 1.3em; color: #1f2937; }
		.Lukic-doc-card-body { flex-grow: 1; margin-bottom: 15px; }
		.Lukic-doc-card-body p { margin: 0; color: #4b5563; font-size: 1.1em; line-height: 1.6; }
		.Lukic-status-badge { font-size: 0.75em; padding: 2px 8px; border-radius: 12px; font-weight: 500; white-space: nowrap; }
		.Lukic-status-active { background-color: #d1fae5; color: #065f46; }
		.Lukic-status-inactive { background-color: #f3f4f6; color: #6b7280; }
		.Lukic-doc-tags { display: flex; flex-wrap: wrap; gap: 5px; }
		.Lukic-tag { background: #f3f4f6; color: #374151; font-size: 0.8em; padding: 2px 8px; border-radius: 4px; }
	' );

	wp_enqueue_script( 'jquery' );
	wp_add_inline_script( 'jquery', '
		jQuery(document).ready(function($) {
			$("#Lukic-doc-search").on("keyup", function() {
				var value = $(this).val().toLowerCase();
				var hasVisible = false;

				$(".Lukic-doc-card").each(function() {
					var searchData = String($(this).data("search") || "").toLowerCase();
					var match = searchData.indexOf(value) > -1;
					
					$(this).toggle(match).toggleClass("is-hidden-by-search", !match);
					if (match) hasVisible = true;
				});

				// Hide empty sections
				$(".Lukic-doc-section").each(function() {
					var sectionHasVisible = $(this).find(".Lukic-doc-card").not(".is-hidden-by-search").length > 0;
					$(this).toggle(sectionHasVisible);
				});
				
				$("#Lukic-doc-no-results").toggle(!hasVisible);
			});
		});
	' );
}
add_action( 'admin_enqueue_scripts', 'Lukic_enqueue_documentation_scripts' );
