<?php
/**
 * Central registry for all snippets and their metadata.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Lukic_Snippet_Registry {
	/**
	 * Cache for localized snippets.
	 *
	 * @var array|null
	 */
	private static $localized_cache = null;

	/**
	 * Category metadata keyed by slug.
	 *
	 * @var array
	 */
	private static $categories = array(
		'admin'       => array(
			'name' => 'Admin Interface',
			'icon' => 'dashicons-admin-appearance',
		),
		'content'     => array(
			'name' => 'Content Management',
			'icon' => 'dashicons-admin-post',
		),
		'utility'     => array(
			'name' => 'Utility',
			'icon' => 'dashicons-admin-tools',
		),
		'media'       => array(
			'name' => 'Media Management',
			'icon' => 'dashicons-admin-media',
		),
		'seo'         => array(
			'name' => 'SEO & Performance',
			'icon' => 'dashicons-chart-line',
		),
		'security'    => array(
			'name' => 'Security',
			'icon' => 'dashicons-shield',
		),
		'development' => array(
			'name' => 'Development',
			'icon' => 'dashicons-editor-code',
		),
	);

	/**
	 * Snippet metadata keyed by snippet slug.
	 *
	 * @var array
	 */
	private static $snippets = array(
		'site_visibility'         => array(
			'file'             => 'snippet-site-visibility.php',
			'name'             => 'Admin Bar Site Visibility Indicator',
			'category'         => 'admin',
			'tags'             => array( 'admin bar', 'indicator', 'search engines' ),
			'description'      => 'Adds a colored indicator in the admin bar to show whether your site is visible to search engines or not.',
			'long_description' => 'This snippet adds a visual indicator to the WordPress admin bar that instantly shows the search engine visibility status of your site. A green indicator means search engines are allowed to index your site, while a red indicator warns you that indexing is discouraged. This is particularly useful for preventing accidental indexing of staging sites or ensuring production sites are visible.',
		),
		'classic_editor'          => array(
			'file'             => 'snippet-classic-editor.php',
			'name'             => 'Enable Classic Editor',
			'category'         => 'content',
			'tags'             => array( 'editor', 'content' ),
			'description'      => 'Restores the classic WordPress editor and the Edit Post screen, making it look like it did before WordPress 5.0.',
			'long_description' => 'Disables the Gutenberg block editor and restores the classic WordPress editor (TinyMCE). This applies to all post types and brings back the familiar "Edit Post" screen layout. Useful if you prefer the traditional writing experience or use plugins that are not fully compatible with the block editor.',
		),
		'wider_admin_menu'        => array(
			'file'             => 'snippet-wider-admin-menu.php',
			'name'             => 'Wider Admin Menu',
			'category'         => 'admin',
			'tags'             => array( 'menu', 'ui', 'interface' ),
			'description'      => 'Makes the WordPress admin menu wider for better readability of longer menu items.',
			'long_description' => 'Increases the width of the WordPress admin sidebar menu. This is helpful if you have plugins with long names that get truncated or wrap awkwardly in the default narrow menu. It improves the overall readability and aesthetics of the admin dashboard.',
		),
		'svg_upload'              => array(
			'file'             => 'snippet-svg-upload.php',
			'name'             => 'SVG Upload Support',
			'category'         => 'media',
			'tags'             => array( 'svg', 'upload' ),
			'description'      => 'Enables SVG file uploads in the WordPress media library with basic sanitization.',
			'long_description' => 'Allows you to upload SVG (Scalable Vector Graphics) files directly to the WordPress Media Library, which is restricted by default for security reasons. This snippet includes basic sanitization to help prevent malicious code injection, but always ensure you trust the source of your SVG files.',
		),
		'post_duplicator'         => array(
			'file'             => 'snippet-post-duplicator.php',
			'name'             => 'Post & Page Duplicator',
			'category'         => 'content',
			'tags'             => array( 'posts', 'pages', 'duplicator' ),
			'description'      => 'Adds a "Duplicate" link to posts, pages and custom post types in admin lists.',
			'long_description' => 'Adds a convenient "Duplicate" link to the row actions for posts, pages, and custom post types in the admin list tables. Clicking this link creates an exact draft copy of the content, including title, content, and custom fields, allowing you to quickly clone content for editing.',
		),
		'show_ids'                => array(
			'file'             => 'snippet-show-ids.php',
			'name'             => 'Show IDs in Admin Tables',
			'category'         => 'admin',
			'tags'             => array( 'admin tables', 'ids', 'posts', 'pages' ),
			'description'      => 'Displays IDs for posts, pages, categories, tags and other taxonomies in admin tables.',
			'long_description' => 'Adds a new column to all admin list tables (posts, pages, categories, tags, users, etc.) that displays the unique numeric ID of each item. This is extremely useful for developers who need IDs for shortcodes, database queries, or plugin configurations.',
		),
		'featured_images'         => array(
			'file'             => 'snippet-featured-images.php',
			'name'             => 'Show Featured Images in Admin Tables',
			'category'         => 'admin',
			'tags'             => array( 'admin tables', 'featured images' ),
			'description'      => 'Shows featured images in admin tables for posts, pages and custom post types.',
			'long_description' => 'Adds a column to the admin list view for posts, pages, and custom post types that displays a thumbnail of the featured image. This allows you to quickly verify which posts have featured images assigned without opening each one individually.',
		),
		'clean_dashboard'         => array(
			'file'             => 'snippet-clean-dashboard.php',
			'name'             => 'Clean Dashboard',
			'category'         => 'admin',
			'tags'             => array( 'dashboard', 'widgets', 'cleanup' ),
			'description'      => 'Removes unnecessary widgets from the WordPress dashboard for a cleaner interface.',
			'long_description' => 'Declutters the main WordPress Dashboard screen by removing default widgets like "WordPress Events and News", "Quick Draft", and "Activity". This creates a cleaner, less distracting starting point for you and your clients.',
		),
		'hide_admin_bar'          => array(
			'file'             => 'snippet-hide-admin-bar.php',
			'name'             => 'Hide Admin Bar on Frontend',
			'category'         => 'admin',
			'tags'             => array( 'admin bar', 'frontend' ),
			'description'      => 'Hides the WordPress admin bar on the frontend of your site for all users.',
			'long_description' => 'Completely removes the black admin toolbar from the top of the website when viewing the frontend. This applies to all logged-in users, including administrators, giving you a true preview of how the site looks to visitors.',
		),
		'media_replace'           => array(
			'file'             => 'snippet-media-replace.php',
			'name'             => 'Media Replacement',
			'category'         => 'media',
			'tags'             => array( 'media', 'replace' ),
			'description'      => 'Replace media files while maintaining the same ID, filename and publish date - keeping all existing links intact.',
			'long_description' => 'Adds a "Replace Media" functionality to the Media Library. This allows you to upload a new file to replace an existing one while preserving the attachment ID, filename, and permalink. This means you don\'t have to update any links or shortcodes that reference the old file.',
			'cleanup'          => array(
				'transients' => array( 'Lukic_media_replace_error', 'Lukic_media_replace_success' ),
			),
		),
		'content_order'           => array(
			'file'             => 'snippet-content-order.php',
			'name'             => 'Content Order',
			'category'         => 'content',
			'tags'             => array( 'content', 'order' ),
			'description'      => 'Allows you to reorder the content of your posts and pages.',
			'long_description' => 'Enables drag-and-drop reordering for posts, pages, and custom post types. This is particularly useful for custom post types where the display order on the frontend matters (e.g., portfolios, team members, FAQs).',
			'cleanup'          => array(
				'options' => array( 'Lukic_content_order_settings' ),
			),
		),
		'show_acf_columns'        => array(
			'file'             => 'snippet-acf-columns.php',
			'name'             => 'Show ACF Fields in Admin Tables',
			'category'         => 'admin',
			'tags'             => array( 'admin tables', 'acf', 'custom fields' ),
			'description'      => 'Adds columns to the admin tables for posts, pages and custom post types to display the values of Advanced Custom Fields (ACF) fields.',
			'long_description' => 'Automatically adds columns to admin list tables for any Advanced Custom Fields (ACF) associated with that post type. This gives you a quick overview of custom field data directly from the post list, saving you from opening each post to check values.',
			'cleanup'          => array(
				'options' => array( 'Lukic_acf_columns_settings' ),
			),
		),
		'media_size_column'       => array(
			'file'             => 'snippet-media-size-column.php',
			'name'             => 'Media Size Column',
			'category'         => 'media',
			'tags'             => array( 'media', 'size' ),
			'description'      => 'Adds a column to the media library to display the file size of each media item.',
			'long_description' => 'Adds a "File Size" column to the Media Library list view. This helps you quickly identify large files that might be impacting your site\'s performance or consuming excessive storage space.',
		),
		'hide_wp_version'         => array(
			'file'             => 'snippet-hide-wp-version.php',
			'name'             => 'Hide WP Version',
			'category'         => 'seo',
			'tags'             => array( 'wp version', 'hide' ),
			'description'      => 'Enhance security by hiding the WordPress version number from your site\'s source view, thwarting targeted attacks.',
			'long_description' => 'Removes the WordPress version number from the site\'s HTML source code, RSS feeds, and scripts. This is a security hardening measure that makes it more difficult for automated scanners to identify your WordPress version and target version-specific vulnerabilities.',
		),
		'disable_xmlrpc'          => array(
			'file'             => 'snippet-disable-xmlrpc.php',
			'name'             => 'Disable XML-RPC',
			'category'         => 'seo',
			'tags'             => array( 'xml-rpc', 'disable' ),
			'description'      => 'Increase security by disabling XML-RPC to prevent external applications from interfacing with your WordPress site, reducing vulnerability to attacks.',
			'long_description' => 'Completely disables the XML-RPC API (`xmlrpc.php`). This API is a common target for brute-force attacks and DDoS attempts. Disabling it improves security and reduces server load, provided you don\'t rely on third-party apps (like the WordPress mobile app) or services (like Jetpack) that use it.',
		),
		'custom_taxonomy_filters' => array(
			'file'             => 'snippet-custom-taxonomy-filters.php',
			'name'             => 'Show Custom Taxonomy Filters',
			'category'         => 'admin',
			'tags'             => array( 'taxonomies', 'filters' ),
			'description'      => 'Shows additional filter dropdowns on list tables for hierarchical and non-hierarchical custom taxonomies. Works for both default and custom post types.',
			'long_description' => 'Adds dropdown filters for custom taxonomies to the admin list tables of custom post types. This makes it much easier to filter and manage content based on your custom classification systems, similar to how you can filter posts by Category.',
		),
		'hide_admin_notices'      => array(
			'file'             => 'snippet-hide-admin-notices.php',
			'name'             => 'Hide Admin Notices',
			'category'         => 'admin',
			'tags'             => array( 'notices', 'cleanup', 'interface' ),
			'description'      => 'Hide unnecessary admin notices and notifications in the WordPress dashboard, creating a cleaner interface with all notices accessible through a dedicated panel in the admin bar.',
			'long_description' => 'Moves all admin notices (success messages, warnings, promotional banners) into a hidden panel accessible via a "Notices" button in the admin bar. This keeps your dashboard clean and prevents notices from pushing down your content, while still allowing you to check them when needed.',
		),
		'custom_admin_footer'     => array(
			'file'             => 'snippet-custom-admin-footer.php',
			'name'             => 'Custom Admin Footer Text',
			'category'         => 'admin',
			'tags'             => array( 'footer', 'interface', 'customization' ),
			'description'      => 'Change or remove the texts at the bottom left ("Thank you for creating with WordPress") and bottom right (version number) of the admin dashboard.',
			'long_description' => 'Allows you to fully customize the text displayed at both the bottom-left and bottom-right of the WordPress admin area, or remove them entirely. Great for white-labeling the dashboard for clients.',
			'cleanup'          => array(
				'options' => array( 'Lukic_custom_admin_footer_text' ),
			),
		),
		'fluid_typography'        => array(
			'file'             => 'snippet-fluid-typography.php',
			'name'             => 'Fluid Typography Calculator',
			'category'         => 'utility',
			'tags'             => array( 'typography', 'calculator' ),
			'description'      => 'Calculates the optimal font sizes for your website based on the screen size.',
			'long_description' => 'Provides a utility to implement fluid typography, where font sizes scale smoothly between a minimum and maximum viewport width. This ensures your text looks perfect on any device, from mobile phones to large desktop monitors, without needing complex media queries.',
		),
		'maintenance_mode'        => array(
			'file'             => 'snippet-maintenance-mode.php',
			'name'             => 'Maintenance Mode',
			'category'         => 'utility',
			'tags'             => array( 'maintenance', 'mode' ),
			'description'      => 'Displays a customizable maintenance mode page to visitors while you work on your site. Administrators can still access the site normally.',
			'long_description' => 'Puts your site into maintenance mode, displaying a customizable "Under Construction" page to all non-logged-in visitors. Administrators and logged-in users can still access the site normally to perform updates or changes. Includes options for custom titles, messages, and colors.',
			'lifecycle'        => array(
				'activate'   => array( 'Lukic_Maintenance_Mode', 'activate_snippet' ),
				'deactivate' => array( 'Lukic_Maintenance_Mode', 'deactivate_snippet' ),
			),
			'cleanup'          => array(
				'options' => array( 'Lukic_maintenance_mode_options' ),
			),
		),
		'last_login'              => array(
			'file'             => 'snippet-last-login.php',
			'name'             => 'Last Login User',
			'category'         => 'utility',
			'tags'             => array( 'login', 'user' ),
			'description'      => 'Adds a "Last login" column to the users table showing when each user last logged in. For users who have never logged in, it displays "No data".',
			'long_description' => 'Adds a column to the Users list table showing the date and time of each user\'s last login. This is helpful for auditing user activity, identifying inactive accounts, or monitoring security.',
		),
		'search_by_slug'          => array(
			'file'             => 'snippet-search-by-slug.php',
			'name'             => 'Search Posts by Slug',
			'category'         => 'content',
			'tags'             => array( 'search', 'posts', 'slug' ),
			'description'      => 'Enhances WordPress admin search to include post slugs in search results for both regular posts and custom post types. Supports multilingual websites by filtering results for the current language only.',
			'long_description' => 'Extends the default admin search functionality to include post slugs (URL paths). By default, WordPress only searches titles and content. This allows you to find a specific page or post by searching for its URL slug.',
		),
		'show_template'           => array(
			'file'             => 'snippet-show-template.php',
			'name'             => 'Show Current Template',
			'category'         => 'admin',
			'tags'             => array( 'template', 'development', 'admin bar' ),
			'description'      => 'Displays the current template file name in the admin bar, helping developers identify which template file is being used on each page. Includes detailed information on hover.',
			'long_description' => 'A developer tool that displays the name of the current theme template file (e.g., `page.php`, `single.php`) in the admin bar on the frontend. Hovering over it reveals the full path and hierarchy. Essential for theme development and debugging.',
		),
		'login_page_designer'     => array(
			'file'             => 'snippet-login-page-designer.php',
			'name'             => 'Login Page Designer',
			'category'         => 'admin',
			'tags'             => array( 'login', 'branding', 'design', 'customization' ),
			'description'      => 'Visually customize the WordPress login page with logo, background, form card, colors, and button styling.',
			'long_description' => 'A full visual designer for the WordPress login screen. Upload a custom logo, choose a solid color, image, or gradient background, style the form card with custom colors, border-radius and shadow, fine-tune label and link colors, and fully style the submit button without touching code.',
			'cleanup'          => array(
				'options' => array( 'Lukic_login_page_designer_options' ),
			),
		),
		'word_counter'            => array(
			'file'             => 'snippet-word-counter.php',
			'name'             => 'Word Counter',
			'category'         => 'admin',
			'tags'             => array( 'content', 'word count', 'analysis' ),
			'description'      => 'Add a word counter tool to analyze text for words, characters, sentences, and paragraphs.',
			'long_description' => 'Adds a comprehensive content analysis tool to the post editor. It counts words, characters, sentences, and paragraphs, and provides reading time estimates. Useful for writers and SEOs who need to hit specific content length targets.',
		),
		'image_attributes_editor' => array(
			'file'             => 'snippet-image-attributes-editor.php',
			'name'             => 'Image Attributes Editor',
			'category'         => 'development',
			'tags'             => array( 'image', 'attributes' ),
			'description'      => 'Edit image attributes such as alt text, title, and description.',
			'long_description' => 'Provides an interface to bulk edit image attributes like Alt Text, Title, Caption, and Description directly from the Media Library list view. This streamlines the process of optimizing image SEO and accessibility.',
		),
		'meta_tags_editor'        => array(
			'file'             => 'snippet-meta-tags-editor.php',
			'name'             => 'Meta Tags Editor',
			'category'         => 'seo',
			'tags'             => array( 'meta tags', 'editor' ),
			'description'      => 'Edit meta titles and descriptions for all pages, posts, custom post types, and taxonomies. Compatible with Yoast SEO and Rank Math plugins.',
			'long_description' => 'Adds fields to edit the SEO Meta Title and Meta Description for posts, pages, and taxonomies. It is designed to be a lightweight alternative to heavy SEO plugins, or to work alongside them if needed.',
		),
		'db_tables_manager'       => array(
			'file'             => 'snippet-db-tables-manager.php',
			'name'             => 'Custom Database Tables Manager',
			'category'         => 'development',
			'tags'             => array( 'database', 'tables' ),
			'description'      => 'Inspect, search, edit, and export database table data from the WordPress admin.',
			'long_description' => 'A database utility for developers to inspect table structure, browse table rows, search validated columns, edit rows with a primary key, and export CSV data directly from the WordPress admin.',
		),
		'redirect_manager'        => array(
			'file'             => 'snippet-redirect-manager.php',
			'name'             => 'Redirect Manager',
			'category'         => 'seo',
			'tags'             => array( 'redirects', 'manager' ),
			'description'      => 'Create and manage 301, 302, 307, and 308 redirects for your website. Tracks redirect usage and provides a clean interface for managing URL redirections.',
			'long_description' => 'A full-featured redirect manager that allows you to easily set up 301 (Permanent), 302 (Found), and other redirects. It tracks how many times each redirect is used and when it was last accessed, helping you clean up old or unused redirects.',
			'cleanup'          => array(
				'options' => array( 'Lukic_redirect_track_hits', 'Lukic_redirect_log_last_access' ),
				'tables'  => array( 'lukic_redirects' ),
			),
		),
		'image_sizes_panel'       => array(
			'file'             => 'snippet-image-sizes-panel.php',
			'name'             => 'Image Sizes Panel',
			'category'         => 'media',
			'tags'             => array( 'media', 'images', 'sizes' ),
			'description'      => 'Displays available image sizes with dimensions in the sidebar when viewing a single image in the WordPress admin dashboard.',
			'long_description' => 'Adds a panel to the "Edit Media" screen that lists all registered image sizes (thumbnail, medium, large, and custom sizes) along with their dimensions and URLs. This helps you verify that your theme and plugins are generating the correct image sizes.',
		),
		'limit_revisions'         => array(
			'file'             => 'snippet-limit-revisions.php',
			'name'             => 'Limit Revisions',
			'category'         => 'utility',
			'tags'             => array( 'revisions', 'database', 'optimization' ),
			'description'      => 'Prevent database bloat by limiting the number of revisions to keep for post types supporting revisions. Configure limits per post type.',
			'long_description' => 'Allows you to limit the number of post revisions stored in the database for each post type. By keeping only the last few revisions (e.g., 5 or 10), you can significantly reduce database size and improve performance without losing the ability to undo recent changes.',
			'cleanup'          => array(
				'options' => array( 'Lukic_limit_revisions_options' ),
			),
		),
		'security_headers'        => array(
			'file'             => 'snippet-security-headers.php',
			'name'             => 'Security Headers Manager',
			'category'         => 'security',
			'tags'             => array( 'security', 'headers', 'protection' ),
			'description'      => 'Enhance your site security by managing HTTP security headers like Content-Security-Policy, X-Frame-Options, and HSTS. Includes presets and testing tools.',
			'long_description' => 'A comprehensive manager for HTTP security headers. It allows you to configure headers like Content-Security-Policy (CSP), X-Frame-Options, X-XSS-Protection, and Strict-Transport-Security (HSTS) to protect your site against clickjacking, XSS, and other attacks.',
			'cleanup'          => array(
				'options' => array( 'Lukic_security_headers' ),
			),
		),
		'hide_author_slugs'       => array(
			'file'             => 'snippet-hide-author-slugs.php',
			'name'             => 'Hide Author Slugs',
			'category'         => 'security',
			'tags'             => array( 'security', 'author', 'slugs' ),
			'description'      => 'Protects author usernames by encrypting URL slugs and securing REST API endpoints.',
			'long_description' => 'Enhances security by obfuscating author URL slugs (e.g., changing `/author/username` to `/author/encrypted-string`). It also restricts access to user data via the REST API, making it much harder for attackers to harvest usernames for brute-force attacks.',
		),
		'admin_menu_organizer'    => array(
			'file'             => 'snippet-admin-menu-organizer.php',
			'name'             => 'Admin Menu Organizer',
			'category'         => 'admin',
			'tags'             => array( 'admin', 'menu', 'organizer' ),
			'description'      => 'Reorder, rename, hide, and reorganize admin menu items using a drag-and-drop interface.',
			'long_description' => 'Gives you full control over the WordPress admin menu. You can drag and drop to reorder items, rename menus to be more intuitive for clients, hide unnecessary items, and organize the dashboard exactly how you want it.',
			'cleanup'          => array(
				'options' => array( 'lukic_admin_menu_settings' ),
			),
		),
		'user_profile_image'      => array(
			'file'             => 'snippet-user-profile-image.php',
			'name'             => 'User Profile Image',
			'category'         => 'media',
			'tags'             => array( 'avatar', 'profile', 'media', 'user' ),
			'description'      => 'Allow users to upload custom avatars from the media library instead of using Gravatar.',
			'long_description' => 'Allows users to upload a custom profile picture directly to their user profile, bypassing Gravatar. This is great for sites where users may not have Gravatar accounts or where you want to host all user images locally.',
		),
		'show_active_plugins_first' => array(
			'file'             => 'snippet-show-active-plugins-first.php',
			'name'             => 'Show Active Plugins First',
			'category'         => 'admin',
			'tags'             => array( 'plugins', 'order', 'admin' ),
			'description'      => 'Show active plugins at the top of plugins list separated from inactive plugins for easier management.',
			'long_description' => 'Reorders the main Plugins list table so that all active plugins are displayed at the top, followed by inactive ones. This makes it easier to see what is currently running on your site and manage your plugins.',
		),
		'disable_comments'        => array(
			'file'             => 'snippet-disable-comments.php',
			'name'             => 'Disable Comments',
			'category'         => 'utility',
			'tags'             => array( 'comments', 'disable', 'spam', 'cleanup' ),
			'description'      => 'Remove comment functionality across your WordPress site, helping reduce spam, moderation workload, and database clutter. Keeps WooCommerce product reviews intact.',
			'long_description' => 'Globally disables the commenting system on your site. It removes comment forms from posts and pages, hides existing comments, and removes comment-related menu items and widgets. It intelligently preserves WooCommerce product reviews if you are running an online store.',
		),
	);

	/**
	 * Get all snippets.
	 *
	 * @return array
	 */
	public static function get_snippets() {
		if ( null !== self::$localized_cache ) {
			return self::$localized_cache;
		}

		$localized = array();

		foreach ( self::$snippets as $snippet_id => $snippet ) {
			$localized[ $snippet_id ] = self::localize_snippet( $snippet );
		}

		self::$localized_cache = $localized;
		return self::$localized_cache;
	}

	/**
	 * Get just the snippet file definitions to avoid unnecessary localization.
	 *
	 * @return array
	 */
	public static function get_snippet_files() {
		$files = array();

		foreach ( self::$snippets as $snippet_id => $snippet ) {
			$files[ $snippet_id ] = $snippet['file'];
		}

		return $files;
	}

	/**
	 * Get a specific snippet definition.
	 *
	 * @param string $snippet_id
	 * @return array|null
	 */
	public static function get_snippet( $snippet_id ) {
		if ( ! isset( self::$snippets[ $snippet_id ] ) ) {
			return null;
		}

		return self::localize_snippet( self::$snippets[ $snippet_id ] );
	}

	/**
	 * Get category map.
	 *
	 * @return array
	 */
	public static function get_categories() {
		$categories = array();

		foreach ( self::$categories as $category_id => $category ) {
			$categories[ $category_id ] = array(
				'name' => $category['name'],
				'icon' => $category['icon'],
			);
		}

		return $categories;
	}

	/**
	 * Get snippets grouped by category.
	 *
	 * @return array
	 */
	public static function get_snippets_by_category() {
		$grouped = array();

		foreach ( self::get_snippets() as $snippet_id => $snippet ) {
			$category = $snippet['category'];
			if ( ! isset( $grouped[ $category ] ) ) {
				$grouped[ $category ] = array();
			}

			$grouped[ $category ][ $snippet_id ] = array(
				'name'    => $snippet['name'],
				'tags'    => isset( $snippet['tags'] ) ? $snippet['tags'] : array(),
				'warning' => isset( $snippet['warning'] ) ? $snippet['warning'] : false,
			);
		}

		return $grouped;
	}

	/**
	 * Get aggregated cleanup metadata for uninstall routines.
	 *
	 * @return array
	 */
	public static function get_cleanup_items() {
		$cleanup = array(
			'options'    => array(),
			'tables'     => array(),
			'transients' => array(),
		);

		foreach ( self::$snippets as $snippet ) {
			if ( isset( $snippet['cleanup']['options'] ) && is_array( $snippet['cleanup']['options'] ) ) {
				$cleanup['options'] = array_merge( $cleanup['options'], $snippet['cleanup']['options'] );
			}

			if ( isset( $snippet['cleanup']['tables'] ) && is_array( $snippet['cleanup']['tables'] ) ) {
				$cleanup['tables'] = array_merge( $cleanup['tables'], $snippet['cleanup']['tables'] );
			}

			if ( isset( $snippet['cleanup']['transients'] ) && is_array( $snippet['cleanup']['transients'] ) ) {
				$cleanup['transients'] = array_merge( $cleanup['transients'], $snippet['cleanup']['transients'] );
			}
		}

		$cleanup['options']    = array_values( array_unique( $cleanup['options'] ) );
		$cleanup['tables']     = array_values( array_unique( $cleanup['tables'] ) );
		$cleanup['transients'] = array_values( array_unique( $cleanup['transients'] ) );

		return $cleanup;
	}

	/**
	 * Prepare snippet data with localized strings.
	 *
	 * @param array $snippet
	 * @return array
	 */
	private static function localize_snippet( $snippet ) {
		// Note: Strings are defined as literals in self::$snippets and are extracted
		// by the translation parser directly from there. We do not wrap them with
		// __() here as the parser cannot read variable values at runtime.

		if ( isset( $snippet['tags'] ) && is_array( $snippet['tags'] ) ) {
			$snippet['tags'] = array_map(
				function ( $tag ) {
					return $tag;
				},
				$snippet['tags']
			);
		}

		return $snippet;
	}
}
