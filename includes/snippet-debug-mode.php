<?php
/**
 * Debug Mode snippet
 *
 * Enables WordPress debugging tools and logs
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class Lukic_Debug_Mode {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Add dashboard widget
        add_action( 'wp_dashboard_setup', array( $this, 'add_dashboard_widget' ) );
        
        // Add admin notice
        add_action( 'admin_notices', array( $this, 'display_admin_notice' ) );
    }

    /**
     * Display a warning notice that debug mode is active.
     */
    public function display_admin_notice() {
        // Only show to admins
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        // Verify snippet is active in settings
        $options = get_option( 'Lukic_snippet_codes_options', array() );
        if ( empty( $options['debug_mode'] ) || (int) $options['debug_mode'] !== 1 ) {
            return;
        }

        $class = 'notice notice-warning is-dismissible';
        $message = __( '<strong>Lukic Code Snippets:</strong> Debug Mode is currently ACTIVE. Performance may be impacted and sensitive data may be logged. Please disable it when troubleshooting is complete.', 'lukic-code-snippets' );

        printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), wp_kses_post( $message ) );
    }

    /**
     * Add the dashboard widget for reading debug.log
     */
    public function add_dashboard_widget() {
        // Verify snippet is active in settings
        $options = get_option( 'Lukic_snippet_codes_options', array() );
        if ( empty( $options['debug_mode'] ) || (int) $options['debug_mode'] !== 1 ) {
            return;
        }

        if ( current_user_can( 'manage_options' ) ) {
            wp_add_dashboard_widget(
                'lukic_debug_log_widget',
                __( 'Debug Log Viewer (Lukic Code Snippets)', 'lukic-code-snippets' ),
                array( $this, 'render_dashboard_widget' )
            );
        }
    }

    /**
     * Render the dashboard widget content
     */
    public function render_dashboard_widget() {
        $log_file = WP_CONTENT_DIR . '/debug.log';
        
        if ( ! file_exists( $log_file ) ) {
            echo '<p>' . esc_html__( 'No debug.log file found. Your site might not be generating any errors currently.', 'lukic-code-snippets' ) . '</p>';
            return;
        }

        $file_size = filesize( $log_file );
        
        if ( $file_size === 0 ) {
            echo '<p>' . esc_html__( 'The debug.log file is currently empty.', 'lukic-code-snippets' ) . '</p>';
            return;
        }

        // Read the last 50 lines if the file is large
        $lines = $this->tail_file( $log_file, 50 );

        echo '<div style="margin-bottom: 10px;">';
        echo '<strong>' . esc_html__( 'File size:', 'lukic-code-snippets' ) . '</strong> ' . esc_html( size_format( $file_size ) );
        echo '</div>';
        
        echo '<div style="background: #111; color: #0f0; padding: 10px; height: 300px; overflow-y: scroll; font-family: monospace; font-size: 12px; white-space: pre-wrap; word-break: break-all;">';
        if ( empty( $lines ) ) {
            echo esc_html__( 'Could not read log file.', 'lukic-code-snippets' );
        } else {
            foreach ( $lines as $line ) {
                echo esc_html( $line );
            }
        }
        echo '</div>';
    }

    /**
     * Helper to tail a file effectively using WP_Filesystem
     */
    private function tail_file( $filepath, $lines = 50 ) {
        global $wp_filesystem;

        if ( empty( $wp_filesystem ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            WP_Filesystem();
        }

        if ( ! $wp_filesystem->exists( $filepath ) ) {
            return false;
        }

        $content = $wp_filesystem->get_contents( $filepath );
        if ( $content === false ) {
            return false;
        }

        $all_lines = explode( "\n", $content );
        // Remove trailing empty lines
        while ( ! empty( $all_lines ) && trim( end( $all_lines ) ) === '' ) {
            array_pop( $all_lines );
        }

        $total = count( $all_lines );
        $start = max( 0, $total - $lines );
        $output = array();
        for ( $i = $start; $i < $total; $i++ ) {
            $output[] = $all_lines[ $i ] . "\n";
        }

        return $output;
    }

    /**
     * Modify wp-config.php content to enable/disable debug
     */
    private static function modify_wp_config( $enable ) {
        $config_file = ABSPATH . 'wp-config.php';
        
        if ( ! wp_is_writable( $config_file ) ) {
            error_log( 'Lukic Code Snippets: wp-config.php is not writable.' );
            return false;
        }

        $config_content = file_get_contents( $config_file );
        if ( $config_content === false ) {
            return false;
        }

        $marker_start = '// --- BEGIN Plugin Lukic Code Snippets: Debug Mode ---';
        $marker_end = '// --- END Plugin Lukic Code Snippets: Debug Mode ---';
        
        // Remove existing block
        $pattern = '/' . preg_quote( $marker_start, '/' ) . '.*?' . preg_quote( $marker_end, '/' ) . '\r?\n?/s';
        $config_content = preg_replace( $pattern, '', $config_content );

        if ( $enable ) {
            // Comment out existing WP_DEBUG definitions so ours takes precedence without PHP notices
            $config_content = preg_replace( '/(?<!\/\/\\s)(?<!\/\/)define\(\s*[\'"]WP_DEBUG[\'"]\s*,/', '// Lukic snippet disabled: define( \'WP_DEBUG\',', $config_content );
            $config_content = preg_replace( '/(?<!\/\/\\s)(?<!\/\/)define\(\s*[\'"]WP_DEBUG_LOG[\'"]\s*,/', '// Lukic snippet disabled: define( \'WP_DEBUG_LOG\',', $config_content );
            $config_content = preg_replace( '/(?<!\/\/\\s)(?<!\/\/)define\(\s*[\'"]WP_DEBUG_DISPLAY[\'"]\s*,/', '// Lukic snippet disabled: define( \'WP_DEBUG_DISPLAY\',', $config_content );

            $insertion = $marker_start . "\n";
            $insertion .= "define('WP_DEBUG', true);\n";
            $insertion .= "define('WP_DEBUG_LOG', true);\n";
            $insertion .= "define('WP_DEBUG_DISPLAY', false);\n";
            $insertion .= "@ini_set('display_errors', 0);\n";
            $insertion .= $marker_end . "\n";

            // Insert before "/* That's all"
            $target_string = "/* That's all";
            if ( strpos( $config_content, $target_string ) !== false ) {
                $config_content = str_replace( $target_string, $insertion . "\n" . $target_string, $config_content );
            } else {
                // Fallback: insert before require_once ABSPATH . 'wp-settings.php';
                $fallback_string = "require_once ABSPATH";
                if ( strpos( $config_content, $fallback_string ) !== false ) {
                     $config_content = str_replace( $fallback_string, $insertion . "\n" . $fallback_string, $config_content );
                } else {
                    // Last resort: append to end
                    $config_content .= "\n" . $insertion;
                }
            }
        } else {
            // Restore commented out defines
            $config_content = preg_replace( '/\/\/ Lukic snippet disabled: define\( \'WP_DEBUG\',/', 'define( \'WP_DEBUG\',', $config_content );
            $config_content = preg_replace( '/\/\/ Lukic snippet disabled: define\( \'WP_DEBUG_LOG\',/', 'define( \'WP_DEBUG_LOG\',', $config_content );
            $config_content = preg_replace( '/\/\/ Lukic snippet disabled: define\( \'WP_DEBUG_DISPLAY\',/', 'define( \'WP_DEBUG_DISPLAY\',', $config_content );
        }

        return file_put_contents( $config_file, $config_content ) !== false;
    }

	/**
	 * Lifecycle hook: called when snippet is activated.
	 */
	public static function activate_snippet() {
        self::modify_wp_config( true );
	}

	/**
	 * Lifecycle hook: called when snippet is deactivated.
	 */
	public static function deactivate_snippet() {
        self::modify_wp_config( false );
	}
}

// Initialize the snippet logic for dashboard only
new Lukic_Debug_Mode();
