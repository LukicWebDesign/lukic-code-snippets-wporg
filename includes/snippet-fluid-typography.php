<?php
/**
 * Fluid Typography Calculator Snippet
 *
 * Adds a calculator tool to create fluid typography CSS using clamp().
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Main class for the Fluid Typography Calculator feature
 */
class Lukic_Fluid_Typography {

	/**
	 * Constructor
	 */
	public function __construct() {
		// Add submenu page
		add_action( 'admin_menu', array( $this, 'add_submenu_page' ) );

		// Enqueue scripts and styles
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Add submenu page
	 */
	public function add_submenu_page() {
		add_submenu_page(
			'lukic-code-snippets',         // Parent slug
			__( 'Fluid Typography Calculator', 'lukic-code-snippets' ), // Page title
			__( 'Fluid Typography', 'lukic-code-snippets' ),           // Menu title
			'manage_options',                // Capability
			'lukic-fluid-typography',      // Menu slug
			array( $this, 'render_page' )      // Callback function
		);
	}

	/**
	 * Enqueue scripts and styles
	 */
	public function enqueue_scripts( $hook ) {
		// Only load on our specific page
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
		if ( $page === 'lukic-fluid-typography' ) {
			wp_enqueue_script(
				'Lukic-fluid-typography',
				plugin_dir_url( __FILE__ ) . '../assets/js/fluid-typography.js',
				array( 'jquery' ),
				Lukic_SNIPPET_CODES_VERSION,
				true
			);
		}
	}

	/**
	 * Render the page content
	 */
	public function render_page() {
		// Include the header partial
		// Header component is already loaded in main plugin file

		// Prepare stats for header
		$stats = array(
			array(
				'count' => '5',
				'label' => 'Presets',
			),
			array(
				'count' => 'CSS',
				'label' => 'Output',
			),
		);

		?>
		<div class="wrap Lukic-fluid-typography-wrap">
			<?php Lukic_display_header( __( 'Fluid Typography Calculator', 'lukic-code-snippets' ), $stats ); ?>
			
			<div class="Lukic-settings-intro">
				<p><?php esc_html_e( 'This calculator helps you create fluid typography using CSS clamp() function. It generates responsive font sizes that scale smoothly between your minimum and maximum viewport widths.', 'lukic-code-snippets' ); ?></p>
			</div>
			
			<div class="Lukic-fluid-calculator">
				<div class="Lukic-calculator-columns">
					<div class="Lukic-calculator-column">
						<h2><?php esc_html_e( 'Min Viewport', 'lukic-code-snippets' ); ?></h2>
						<div class="Lukic-input-group">
							<label for="min-viewport-width"><?php esc_html_e( 'Width (px)', 'lukic-code-snippets' ); ?></label>
							<input type="number" id="min-viewport-width" value="320" min="0" step="1">
						</div>
						<div class="Lukic-input-group">
							<label for="min-font-size"><?php esc_html_e( 'Font Size (px)', 'lukic-code-snippets' ); ?></label>
							<input type="number" id="min-font-size" value="16" min="0" step="0.1">
						</div>
						<div class="Lukic-input-group">
							<label for="min-type-scale"><?php esc_html_e( 'Type Scale', 'lukic-code-snippets' ); ?></label>
							<select id="min-type-scale">
								<option value="1.067">Minor Second (1.067)</option>
								<option value="1.125">Major Second (1.125)</option>
								<option value="1.200">Minor Third (1.200)</option>
								<option value="1.250" selected>Major Third (1.250)</option>
								<option value="1.333">Perfect Fourth (1.333)</option>
								<option value="1.414">Augmented Fourth (1.414)</option>
								<option value="1.500">Perfect Fifth (1.500)</option>
								<option value="1.618">Golden Ratio (1.618)</option>
								<option value="custom">Custom...</option>
							</select>
							<input type="number" id="min-custom-scale" value="1.25" min="1" step="0.001" style="display: none;" placeholder="Custom scale ratio">
						</div>
					</div>
					
					<div class="Lukic-calculator-column">
						<h2><?php esc_html_e( 'Max Viewport', 'lukic-code-snippets' ); ?></h2>
						<div class="Lukic-input-group">
							<label for="max-viewport-width"><?php esc_html_e( 'Width (px)', 'lukic-code-snippets' ); ?></label>
							<input type="number" id="max-viewport-width" value="1240" min="0" step="1">
						</div>
						<div class="Lukic-input-group">
							<label for="max-font-size"><?php esc_html_e( 'Font Size (px)', 'lukic-code-snippets' ); ?></label>
							<input type="number" id="max-font-size" value="20" min="0" step="0.1">
						</div>
						<div class="Lukic-input-group">
							<label for="max-type-scale"><?php esc_html_e( 'Type Scale', 'lukic-code-snippets' ); ?></label>
							<select id="max-type-scale">
								<option value="1.067">Minor Second (1.067)</option>
								<option value="1.125">Major Second (1.125)</option>
								<option value="1.200">Minor Third (1.200)</option>
								<option value="1.250">Major Third (1.250)</option>
								<option value="1.333" selected>Perfect Fourth (1.333)</option>
								<option value="1.414">Augmented Fourth (1.414)</option>
								<option value="1.500">Perfect Fifth (1.500)</option>
								<option value="1.618">Golden Ratio (1.618)</option>
								<option value="custom">Custom...</option>
							</select>
							<input type="number" id="max-custom-scale" value="1.333" min="1" step="0.001" style="display: none;" placeholder="Custom scale ratio">
						</div>
					</div>
				</div>
				
				<div class="Lukic-calculate-btn-container">
					<button id="calculate-fluid-typography" class="button button-primary"><?php esc_html_e( 'Calculate Fluid Typography', 'lukic-code-snippets' ); ?></button>
				</div>
				
				<div id="results-container" class="Lukic-results-container">
					<h2><?php esc_html_e( 'Results', 'lukic-code-snippets' ); ?></h2>
					
					<div class="Lukic-preview-container">
						<h3><?php esc_html_e( 'Preview', 'lukic-code-snippets' ); ?></h3>
						<div id="typography-preview" class="Lukic-typography-preview">
							<p class="fluid-text-step-0">This is text at the base size.</p>
							<p class="fluid-text-step-1">This is text one step up.</p>
							<p class="fluid-text-step-2">This is text two steps up.</p>
							<p class="fluid-text-step-3">This is text three steps up.</p>
							<p class="fluid-text-step-4">This is text four steps up.</p>
						</div>
					</div>
					
					<div class="Lukic-code-container">
						<h3><?php esc_html_e( 'CSS Code', 'lukic-code-snippets' ); ?></h3>
						<p><?php esc_html_e( 'Copy and paste this code into your CSS file:', 'lukic-code-snippets' ); ?></p>
						<div class="code-display-container">
							<pre id="css-output" class="Lukic-code-display"></pre>
							<button id="copy-css" class="button"><?php esc_html_e( 'Copy to Clipboard', 'lukic-code-snippets' ); ?></button>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
	}
}

// Initialize the class
if ( ! function_exists( 'Lukic_fluid_typography_init' ) ) {
	function Lukic_fluid_typography_init() {
		new Lukic_Fluid_Typography();
	}

	// Run the initialization
	Lukic_fluid_typography_init();
}
