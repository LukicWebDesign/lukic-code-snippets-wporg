<?php
/**
 * Snippet: Word Counter
 * Description: Adds a word counter tool to analyze text for words, characters, sentences, and paragraphs
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Class Lukic_Word_Counter
 */
class Lukic_Word_Counter {
	/**
	 * Constructor
	 */
	public function __construct() {
		// Add submenu page
		add_action( 'admin_menu', array( $this, 'add_submenu_page' ) );

		// Enqueue scripts
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		// Register AJAX handlers
		add_action( 'wp_ajax_Lukic_analyze_text', array( $this, 'ajax_analyze_text' ) );
	}

	/**
	 * Add submenu page
	 */
	public function add_submenu_page() {
		add_submenu_page(
			'lukic-code-snippets',
			__( 'Word Counter', 'lukic-code-snippets' ),
			__( 'Word Counter', 'lukic-code-snippets' ),
			'manage_options',
			'lukic-word-counter',
			array( $this, 'display_settings_page' )
		);
	}

	/**
	 * Enqueue scripts
	 */
	public function enqueue_scripts( $hook ) {
		if ( strpos( $hook, 'lukic-word-counter' ) === false ) {
			return;
		}

		wp_register_style( 'Lukic-word-counter-styles', false, array(), Lukic_SNIPPET_CODES_VERSION );
		wp_enqueue_style( 'Lukic-word-counter-styles' );
		wp_add_inline_style( 'Lukic-word-counter-styles', '
			.Lukic-word-counter-container { margin-top: 20px; }
			.Lukic-stats-container { display: flex; gap: 20px; margin-bottom: 20px; flex-wrap: wrap; }
			.Lukic-stat-box { padding: 15px 25px; border-radius: 8px; text-align: center; flex: 1; min-width: 150px; }
			.Lukic-stat-box .stat-value { display: block; font-size: 24px; font-weight: bold; margin-bottom: 5px; }
			.Lukic-stat-box .stat-label { font-size: 14px; }
			.Lukic-stat-box.blue { background-color: #4285f4; color: white; }
			.Lukic-stat-box.light-blue { background-color: #45b6fe; color: white; }
			.Lukic-stat-box.green { background-color: #e8f5e9; color: #2e7d32; }
			.Lukic-stat-box.pink { background-color: #fce4ec; color: #c2185b; }
			.Lukic-stat-box.yellow { background-color: #fff3e0; color: #f57c00; }
			.Lukic-textarea-container { margin-top: 20px; }
			#Lukic-text-input { width: 100%; min-height: 300px; padding: 15px; font-size: 16px; border: 1px solid #ddd; border-radius: 8px; resize: vertical; }
		' );

		wp_enqueue_script( 'jquery' );
		wp_localize_script( 'jquery', 'LukicWordCounter', array(
			'nonce' => wp_create_nonce( 'Lukic_word_counter_nonce' ),
		) );

		wp_add_inline_script( 'jquery', '
			jQuery(document).ready(function($) {
				var typingTimer;
				var doneTypingInterval = 500;

				$("#Lukic-text-input").on("input", function() {
					clearTimeout(typingTimer);
					typingTimer = setTimeout(analyzeText, doneTypingInterval);
				});

				function analyzeText() {
					var text = $("#Lukic-text-input").val();

					$.ajax({
						url: ajaxurl,
						type: "POST",
						data: {
							action: "Lukic_analyze_text",
							text: text,
							nonce: LukicWordCounter.nonce
						},
						success: function(response) {
							if (response.success) {
								var data = response.data;
								$("#char-no-spaces").text(data.chars_no_spaces);
								$("#char-with-spaces").text(data.chars_with_spaces);
								$("#word-count").text(data.words);
								$("#sentence-count").text(data.sentences);
								$("#paragraph-count").text(data.paragraphs);
							}
						}
					});
				}
			});
		' );
	}

	/**
	 * Display settings page
	 */
	public function display_settings_page() {
		// Include the header partial
		// Header component is already loaded in main plugin file

		// Prepare stats for header
		$stats = array(
			array(
				'count' => '5',
				'label' => 'Metrics',
			),
			array(
				'count' => 'Real-time',
				'label' => 'Analysis',
			),
		);

		?>
		<div class="wrap Lukic-settings-wrap">
			<?php Lukic_display_header( __( 'Word Counter', 'lukic-code-snippets' ), $stats ); ?>
			
			<div class="Lukic-settings-intro">
				<p><?php esc_html_e( 'Analyze your text for word count, character count, sentences, and paragraphs.', 'lukic-code-snippets' ); ?></p>
			</div>
			
			<div class="Lukic-word-counter-container">
				<div class="Lukic-stats-container">
					<div class="Lukic-stat-box blue">
						<span class="stat-value" id="char-no-spaces">0</span>
						<span class="stat-label"><?php echo esc_html__( 'characters (no spaces)', 'lukic-code-snippets' ); ?></span>
					</div>
					
					<div class="Lukic-stat-box light-blue">
						<span class="stat-value" id="char-with-spaces">0</span>
						<span class="stat-label"><?php echo esc_html__( 'characters (with spaces)', 'lukic-code-snippets' ); ?></span>
					</div>
					
					<div class="Lukic-stat-box green">
						<span class="stat-value" id="word-count">0</span>
						<span class="stat-label"><?php echo esc_html__( 'words', 'lukic-code-snippets' ); ?></span>
					</div>
					
					<div class="Lukic-stat-box pink">
						<span class="stat-value" id="sentence-count">0</span>
						<span class="stat-label"><?php echo esc_html__( 'sentences', 'lukic-code-snippets' ); ?></span>
					</div>
					
					<div class="Lukic-stat-box yellow">
						<span class="stat-value" id="paragraph-count">0</span>
						<span class="stat-label"><?php echo esc_html__( 'paragraphs', 'lukic-code-snippets' ); ?></span>
					</div>
				</div>
				
				<div class="Lukic-textarea-container">
					<textarea id="Lukic-text-input" placeholder="<?php echo esc_attr__( 'Type or paste your text here...', 'lukic-code-snippets' ); ?>"></textarea>
				</div>
			</div>
			</div>
		</div>
		<?php
	}

	/**
	 * AJAX handler for text analysis
	 */
	public function ajax_analyze_text() {
		// Verify nonce
		if ( ! check_ajax_referer( 'Lukic_word_counter_nonce', 'nonce', false ) ) {
			wp_send_json_error( 'Invalid nonce' );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Permission denied' );
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$text = isset( $_POST['text'] ) ? sanitize_textarea_field( wp_unslash( $_POST['text'] ) ) : '';

		$analysis = array(
			'chars_no_spaces'   => strlen( str_replace( ' ', '', $text ) ),
			'chars_with_spaces' => strlen( $text ),
			'words'             => str_word_count( $text ),
			'sentences'         => $this->count_sentences( $text ),
			'paragraphs'        => $this->count_paragraphs( $text ),
		);

		wp_send_json_success( $analysis );
	}

	/**
	 * Count sentences in text
	 */
	private function count_sentences( $text ) {
		$text = trim( $text );
		if ( empty( $text ) ) {
			return 0;
		}

		// Count sentences by looking for periods, exclamation marks, and question marks
		// followed by spaces or end of string
		return preg_match_all( '/[.!?](\s|$)/', $text, $matches );
	}

	/**
	 * Count paragraphs in text
	 */
	private function count_paragraphs( $text ) {
		$text = trim( $text );
		if ( empty( $text ) ) {
			return 0;
		}

		// Split by double newlines and filter out empty paragraphs
		$paragraphs = array_filter( explode( "\n\n", str_replace( "\r\n", "\n", $text ) ) );
		return count( $paragraphs );
	}
}

// Initialize the snippet
new Lukic_Word_Counter();
