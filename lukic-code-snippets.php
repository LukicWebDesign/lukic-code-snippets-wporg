<?php

/**
 * Plugin Name: Lukic Code Snippets
 * Plugin URI: https://wplukic.com/lukic-code-snippets
 * Description: A collection of useful code snippets for WordPress
 * Version: 2.9.1
 * Author: Miloš Lukić
 * Author URI: https://wplukic.com
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: lukic-code-snippets
 * Domain Path: /languages
 */

/*
 * WordPress.org-safe build.
 */



// If this file is called directly, abort.
if (! defined('WPINC')) {
	die;
}

define('Lukic_SNIPPET_CODES_VERSION', '2.9.1');
define('Lukic_SNIPPET_CODES_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('Lukic_SNIPPET_CODES_PLUGIN_URL', plugin_dir_url(__FILE__));
/**
 * The core plugin class
 */
class Lukic_Snippet_Codes
{

	/**
	 * Constructor
	 */
	public function __construct()
	{
		// Load dependencies.
		$this->load_dependencies();

		// Initialize core components for admin only.
		if (is_admin()) {
			$settings      = new Lukic_Snippet_Codes_Settings();
			$asset_manager = new Lukic_Asset_Manager();
		}

		// Localization.
		add_action('init', array($this, 'load_textdomain'), 0);
		// Load activated snippets.
		add_action('plugins_loaded', array($this, 'load_snippets'));

		// Plugin action links.
		add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'add_action_links'));
	}

	/**
	 * Load the required dependencies
	 */
	private function load_dependencies()
	{
		// Core classes.
		require_once Lukic_SNIPPET_CODES_PLUGIN_DIR . 'includes/class-settings.php';
		require_once Lukic_SNIPPET_CODES_PLUGIN_DIR . 'includes/components/class-asset-manager.php';
		require_once Lukic_SNIPPET_CODES_PLUGIN_DIR . 'includes/snippets/class-snippet-registry.php';
		require_once Lukic_SNIPPET_CODES_PLUGIN_DIR . 'includes/snippets/class-snippet-lifecycle.php';

		// Utilities.
		require_once Lukic_SNIPPET_CODES_PLUGIN_DIR . 'includes/utilities/class-helpers.php';

		// Components.
		require_once Lukic_SNIPPET_CODES_PLUGIN_DIR . 'includes/components/header.php';
		require_once Lukic_SNIPPET_CODES_PLUGIN_DIR . 'includes/components/page-documentation.php';
	}

	/**
	 * Load plugin text domain for translations
	 */
	public function load_textdomain()
	{
		// WordPress automatically loads plugins' textdomains from the language directory since WordPress 4.6.
		// So we don't need to manually call load_plugin_textdomain().
	}

	/**
	 * Get total number of available snippets
	 *
	 * @return int Total number of snippets
	 */
	public static function get_total_snippets_count()
	{
		return count(Lukic_Snippet_Registry::get_snippets());
	}

	/**
	 * Get all available snippets
	 *
	 * @return array Array of available snippets
	 */
	public static function get_available_snippets()
	{
		$available = array();
		foreach (Lukic_Snippet_Registry::get_snippets() as $snippet_id => $snippet) {
			$available[$snippet_id] = array(
				'file' => $snippet['file'],
				'name' => $snippet['name'],
			);
		}

		return $available;
	}

	/**
	 * Load activated snippets
	 */
	public function load_snippets()
	{
		// Get plugin options.
		$options = get_option('Lukic_snippet_codes_options', array());

		// Get available snippets.
		$snippets = Lukic_Snippet_Registry::get_snippet_files();

		// Loop through snippets and load activated ones.
		foreach ($snippets as $snippet_id => $file) {
			if (isset($options[$snippet_id]) && 1 === (int) $options[$snippet_id]) {
				$snippet_file = Lukic_SNIPPET_CODES_PLUGIN_DIR . 'includes/' . $file;
				if (file_exists($snippet_file)) {
					require_once $snippet_file;
				}
			}
		}
	}

	/**
	 * Add custom action links on the Plugins page
	 */
	public function add_action_links($links)
	{
		$custom_links = array(
			'<a href="https://wplukic.com" target="_blank" rel="noopener noreferrer" style="color: #00E1AF; font-weight: bold;">' . __('About the Author', 'lukic-code-snippets') . '</a>',
		);
		return array_merge($custom_links, $links);
	}
}

// Initialize the plugin.
new Lukic_Snippet_Codes();
