<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Direct inline styles for settings container.
 * Uses wp_add_inline_style() instead of inline <style> tag.
 */
function lukic_direct_inline_styles() {
	wp_add_inline_style( 'Lukic-admin-styles', '
		.Lukic-settings-container { background: #fff !important; border-radius: 8px !important; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05) !important; }
		.Lukic-stat-box { background: white !important; border-radius: 6px !important; box-shadow: 0 2px 8px rgba(0,0,0,0.05) !important; border: 1px solid #f0f0f0 !important; transition: transform 0.2s !important; }
		.Lukic-checkbox-label { border-radius: 6px !important; border: 1px solid #f0f0f0 !important; }
		.Lukic-submit-container .button-primary { box-shadow: 0 2px 5px rgba(0,225,175,0.2) !important; }
	' );
}
add_action( 'admin_enqueue_scripts', 'lukic_direct_inline_styles' );
