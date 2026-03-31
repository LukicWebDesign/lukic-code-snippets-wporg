It's time to move forward with the plugin review "wplukic"!

Your plugin is not yet ready to be approved, you are receiving this email because the volunteers have manually checked it and have found some issues in the code / functionality of your plugin.

Please check this email thoroughly, address any issues listed, test your changes, and upload a corrected version of your code if all is well.

List of issues found

## Use wp_enqueue commands

Your plugin is not correctly including JS and/or CSS. You should be using the built in functions for this:

When including JavaScript code you can use:
wp_register_script() and wp_enqueue_script() to add JavaScript code from a file.
wp_add_inline_script() to add inline JavaScript code to previous declared scripts.

When including CSS you can use:
wp_register_style() and wp_enqueue_style() to add CSS from a file.
wp_add_inline_style() to add inline CSS to previously declared CSS.

Note that as of WordPress 6.3, you can easily pass attributes like defer or async: https://make.wordpress.org/core/2023/07/14/registering-scripts-with-async-and-defer-attributes-in-wordpress-6-3/

Also, as of WordPress 5.7, you can pass other attributes by using this functions and filters: https://make.wordpress.org/core/2021/02/23/introducing-script-attributes-related-functions-in-wordpress-5-7/

If you're trying to enqueue on the admin pages you'll want to use the admin enqueues.

https://developer.wordpress.org/reference/hooks/admin_enqueue_scripts/
https://developer.wordpress.org/reference/hooks/admin_print_scripts/
https://developer.wordpress.org/reference/hooks/admin_print_styles/

Example(s) from your plugin:
includes/snippet-hide-admin-notices.php:53 <style type="text/css">
includes/snippet-show-template.php:153 <style type="text/css">
includes/snippet-maintenance-mode.php:492 <link rel="stylesheet" href="<?php echo esc_url( Lukic_SNIPPET_CODES_PLUGIN_URL . 'assets/css/maintenance-public.css' ); ?>">
includes/snippet-site-visibility.php:55 <style type="text/css">
includes/snippet-login-page-designer.php:282 <style id="lukic-login-designer">
includes/snippet-media-size-column.php:129 <style type="text/css">
includes/snippet-hide-admin-notices.php:258 <script type="text/javascript">
includes/snippet-admin-menu-organizer.php:270 echo '<style type="text/css">';
... out of a total of 13 incidences.

## Calling core loading files directly

Calling core files like wp-config.php, wp-blog-header.php, wp-load.php directly via an include is not permitted.

These calls are prone to failure as not all WordPress installs have the exact same file structure. In addition it opens your plugin to security issues, as WordPress can be easily tricked into running code in an unauthenticated manner.

Your code should always exist in functions and be called by action hooks. This is true even if you need code to exist outside of WordPress. Code should only be accessible to people who are logged in and authorized, if it needs that kind of access. Your plugin's pages should be called via the dashboard like all the other settings panels, and in that way, they'll always have access to WordPress functions.

https://developer.wordpress.org/plugins/hooks/

There are some exceptions to the rule in certain situations and for certain core files. In that case, we expect you to use require_once to load them and to use a function from that file immediately after loading it.

If you are trying to "expose" an endpoint to be accessed directly by an external service, you have some options.
You can expose a 'page' use query_vars and/or rewrite rules to create a virtual page which calls a function. A practical example.
You can create an AJAX endpoint.
You can create a REST API endpoint.

Example(s) from your plugin:
includes/snippet-custom-login-url.php:277 require_once ABSPATH . 'wp-login.php';

## Determine files and directories locations correctly

WordPress provides several functions for easily determining where a given file or directory lives.

We detected that the way your plugin references some files, directories and/or URLs may not work with all WordPress setups. This happens because there are hardcoded references or you are using the WordPress internal constants.

Let's improve it, please check out the following documentation:

https://developer.wordpress.org/plugins/plugin-basics/determining-plugin-and-content-directories/

It contains all the functions available to determine locations correctly.

Most common cases in plugins can be solved using the following functions:
For where your plugin is located: plugin_dir_path() , plugin_dir_url() , plugins_url()
For the uploads directory: wp_upload_dir() (Note: If you need to write files, please do so in a folder in the uploads directory, not in your plugin directories).

Example(s) from your plugin:
includes/snippet-upload-limits.php:277 $user_ini_file = ABSPATH . '.user.ini';
includes/snippet-upload-limits.php:280 if ( ( file_exists( $user_ini_file ) && ! wp_is_writable( $user_ini_file ) ) || ( ! file_exists( $user_ini_file ) && ! wp_is_writable( ABSPATH ) ) ) {
-----> ABSPATH

## Don't Force Set PHP Limits Globally

While many plugins can need optimal settings for PHP, we ask you please not set them as global defaults.

Having defines like ini_set('memory_limit', '-1'); run globally (like on init or in the \_\_construct() part of your code) means you'll be running that for everything on the site, which may cause your users to fall out of compliance with any limits or restrictions on their host.

If you must use those, you need to limit them specifically to only the exact functions that require them.

Example(s) from your plugin:
includes/snippet-upload-limits.php:195 ini_set('memory_limit', $settings['memory_limit']);
includes/snippet-upload-limits.php:193 ini_set('max_execution_time', $settings['max_execution_time']);

## Including An Update Checker / Changing Updates functionality

Please remove the checks you have in your plugin to provide for updates.

We do not permit plugins to phone home to other servers for updates, as we are providing that service for you with WordPress.org hosting. One of our guidelines is that you actually use our hosting, so we need you to remove that code.

We also ask that plugins not interfere with the built-in updater as it will cause users to have unexpected results with WordPress 5.5 and up.

Example(s) from your plugin:
includes/snippet-disable-all-updates.php:39 remove_action('wp_version_check', 'wp_version_check');
includes/snippet-disable-all-updates.php:28 add_filter('auto_update_translation', '**return_false');
includes/snippet-disable-all-updates.php:44 add_filter('auto_update_translation', '**return_false');
includes/snippet-disable-all-updates.php:40 remove_action('load-update-core.php', 'wp_update_plugins');
includes/snippet-disable-all-updates.php:32 add_filter('auto_update_plugin', '**return_false');
includes/snippet-disable-all-updates.php:77 remove_action('wp_update_plugins', 'wp_update_plugins');
includes/snippet-disable-all-updates.php:38 remove_action('admin_init', '\_maybe_update_core');
includes/snippet-disable-all-updates.php:33 add_filter('auto_update_theme', '**return_false');
... out of a total of 12 incidences.

## Saving data in the plugin folder and/or asking users to edit/write to plugin.

We cannot accept a plugin that forces (or tells) users to edit the plugin files in order to function, or saves data in the plugin folder.

Plugin folders are deleted when upgraded, so using them to store any data is problematic. Also bear in mind, any data saved in a plugin folder is accessible by the public. This means anyone can read it and use it without the site-owner’s permission.

It is preferable that you save your information to the database, via the Settings API, especially if it’s privileged data.

If that’s not possible, because you’re uploading media files, you should use the media uploader.

If you can’t do either of those, you must save the data outside the plugins folder. We recommend using the uploads directory, creating a folder there with the slug of your plugin as name, as that will make your plugin compatible with multisite and other one-off configurations.

Please refer to the following links:

https://developer.wordpress.org/plugins/settings/
https://developer.wordpress.org/reference/functions/media_handle_upload/
https://developer.wordpress.org/reference/functions/wp_handle_upload/
https://developer.wordpress.org/reference/functions/wp_upload_dir/

Example(s) from your plugin:
includes/snippet-upload-limits.php:302 file_put_contents($user_ini_file, ltrim($user_ini_content, "\n"));

# ↳ Detected: ABSPATH

## Unclosed ob_start()

Using ob_start() without explicitly closing the buffer within the same logical flow (e.g., using ob_get_clean() , ob_end_flush() , or similar) can lead to unpredictable behaviour.

While output buffering is a valid technique, you should not leave a buffer 'open'.

WordPress is a shared environment in which the core, plugins and themes run in a coordinated sequence that isn't always predictable, particularly due to the way hooks work. If another component opens or closes a buffer that doesn't align with yours, the buffer stack can become misaligned, resulting in unexpected behaviour.

If you need to modify the entire response output, you can do so in a standardised way since WordPress 6.9 using the template enhancement output buffer.

Please ensure that every instance of ob_start() you create is paired with a corresponding closing function (like ob_get_clean() ) within the same function scope, and that nothing (including hooks and your code logic) can intercept or bypass that closing logic.

Example(s) from your plugin:
includes/snippet-admin-notifications.php:85 ob_start();

## Changing global behaviour

Changes to global settings, parameters, configurations or function behaviour can have wide-ranging and sometimes unintended effects on the execution of other plugins, themes and even the WordPress core itself.

This can result in unexpected behaviour, conflicts or security issues elsewhere.

If this was not the intention, this plugin should perform these changes in a more targeted way to ensure they only affect the relevant functionality.

From your plugin:
includes/snippet-disable-file-editing.php:23 define('DISALLOW_FILE_EDIT', true);

## Nonces and User Permissions Needed for Security

Please add a nonce check to your input calls ($\_POST, $\_GET, $REQUEST) to prevent unauthorized access.

If you use wp*ajax* to trigger submission checks, remember they also need a nonce check.

👮 Checking permissions: Keep in mind, a nonce check alone is not bulletproof security. Do not rely on nonces for authorization purposes. When needed, use it together with current_user_can() in order to prevent users without the right permissions from accessing things they shouldn't.

Also make sure that the nonce logic is correct by making sure it cannot be bypassed. Checking the nonce with current_user_can() is great, but mixing it with other checks can make the condition more complex and, without realising it, bypassable, remember that anything can be sent through an input, don't trust any input.

Keep performance in mind. Don't check for post submission outside of functions. Doing so means that the check will run on every single load of the plugin, which means that every single person who views any page on a site using your plugin will be checking for a submission. This will make your code slow and unwieldy for users on any high traffic site, leading to instability and eventually crashes.

The following links may assist you in development:

https://developer.wordpress.org/plugins/security/nonces/
https://developer.wordpress.org/plugins/javascript/ajax/#nonce
https://developer.wordpress.org/plugins/settings/settings-api/

From your plugin:
includes/snippet-media-replace.php:125 Lukic_media_replace_page() [function] No nonce check found validating input origin on lines 125-178

# ↳ Line 128: $attachment_id = isset( $\_GET['attachment_id'] ) ? intval( wp_unslash( $\_GET['attachment_id'] ) ) : 0;

includes/snippet-content-order.php:281 Lukic_content_order_interface() [function] No nonce check found validating input origin on lines 281-325

# ↳ Line 324: $current_parent = absint( wp_unslash( $\_GET['parent'] ) );

includes/snippet-custom-taxonomy-filters.php:74 Lukic_display_taxonomy_terms() [function] No nonce check found validating input origin on lines 74-87

# ↳ Line 76: $selected = isset( $\_GET[ $taxonomy->name ] ) ? sanitize_text_field( wp_unslash( $\_GET[ $taxonomy->name ] ) ) : '';

## Data Must be Sanitized, Escaped, and Validated

When you include POST/GET/REQUEST/FILE calls in your plugin, it's important to sanitize, validate, and escape them. The goal here is to prevent a user from accidentally sending trash data through the system, as well as protecting them from potential security issues.

SANITIZE: Data that is input (either by a user or automatically) must be sanitized as soon as possible. This lessens the possibility of XSS vulnerabilities and MITM attacks where posted data is subverted.

VALIDATE: All data should be validated, no matter what. Even when you sanitize, remember that you don’t want someone putting in ‘dog’ when the only valid values are numbers.

ESCAPE: Data that is output must be escaped properly when it is echo'd, so it can't hijack admin screens. There are many esc\_\*() functions you can use to make sure you don't show people the wrong data.

To help you with this, WordPress comes with a number of sanitization and escaping functions. You can read about those here:

https://developer.wordpress.org/apis/security/sanitizing/
https://developer.wordpress.org/apis/security/escaping/

Remember: You must use the most appropriate functions for the context. If you’re sanitizing email, use sanitize_email() , if you’re outputting HTML, use wp_kses_post() , and so on.

An easy mantra here is this:

Sanitize early
Escape Late
Always Validate

Clean everything, check everything, escape everything, and never trust the users to always have input sane data. After all, users come from all walks of life.

Example(s) from your plugin:
includes/snippet-upload-limits.php:400 $file = $\_FILES['test_file'];

# ↳ Line 404: $error_message = $this->get_upload_error_message( $file['error'] );

# ↳ Line 409: 'file_size' => size_format( $file['size'] ),

# ↳ Line 420: 'file_size' => size_format( $file['size'] ),

includes/snippet-media-replace.php:340 $uploaded_file = $\_FILES['replacement_file'];

# ↳ Line 341: $uploaded_file_info = pathinfo( $uploaded_file['name'] );

# ↳ Line 367: $new_file_path = $original_file_info['dirname'] . '/' . $new_filename;

# ↳ Line 376: $move_result = @copy( $uploaded_file['tmp_name'], $new_file_path );

# ↳ Line 378: wp_delete_file( $uploaded_file['tmp_name'] );

includes/snippet-content-order.php:234 $post_ids = isset( $\_POST['post_ids'] ) ? wp_unslash( (array) $\_POST['post_ids'] ) : array();

# ↳ Line 246: $post = get_post( $post_id );

# ↳ Line 253: 'ID' => $post_id,

# ↳ Line 254: 'menu_order' => $menu_order,

includes/snippet-db-tables-manager.php:701 $row_data = isset( $\_POST['row_data'] ) ? wp_unslash( (array) $\_POST['row_data'] ) : array();

# ↳ Line 720: $column = $this->validate_column_name( $column );

✔️ You can check this using Plugin Check.

## Variables and options must be escaped when echo'd

Much related to sanitizing everything, all variables that are echoed need to be escaped when they're echoed, so it can't hijack users or (worse) admin screens. There are many esc\_\*() functions you can use to make sure you don't show people the wrong data, as well as some that will allow you to echo HTML safely.

At this time, we ask you escape all $-variables, options, and any sort of generated data when it is being echoed. That means you should not be escaping when you build a variable, but when you output it at the end. We call this 'escaping late.'

Besides protecting yourself from a possible XSS vulnerability, escaping late makes sure that you're keeping the future you safe. While today your code may be only outputted hardcoded content, that may not be true in the future. By taking the time to properly escape when you echo, you prevent a mistake in the future from becoming a critical security issue.

This remains true of options you've saved to the database. Even if you've properly sanitized when you saved, the tools for sanitizing and escaping aren't interchangeable. Sanitizing makes sure it's safe for processing and storing in the database. Escaping makes it safe to output.

Also keep in mind that sometimes a function is echoing when it should really be returning content instead. This is a common mistake when it comes to returning JSON encoded content. Very rarely is that actually something you should be echoing at all. Echoing is because it needs to be on the screen, read by a human. Returning (which is what you would do with an API) can be json encoded, though remember to sanitize when you save to that json object!

There are a number of options to secure all types of content (html, email, etc). Yes, even HTML needs to be properly escaped.

https://developer.wordpress.org/apis/security/escaping/

Remember: You must use the most appropriate functions for the context. There is pretty much an option for everything you could echo. Even echoing HTML safely.

Example(s) from your plugin:
includes/snippet-login-page-designer.php:366 echo wp_strip_all_tags( $opts['custom_css'] );
includes/snippet-admin-notifications.php:119 echo Lukic_organize_notifications( $notices );
includes/snippet-admin-menu-organizer.php:273 echo $css;
includes/snippet-login-page-designer.php:287 echo $background_css;

# ↳ Detected origin: $this->build_background_css($opts)

# ↳ Remember to ALWAYS escape as LATE as possible as with a PROPER function for the context.

✔️ You can check this using Plugin Check.

## Unsafe SQL calls

When making database calls, it's highly important to protect your code from SQL injection vulnerabilities. You need to update your code to use wpdb calls and prepare() with your queries to protect them.

Please review the following:
https://developer.wordpress.org/reference/classes/wpdb/#protect-queries-against-sql-injection-attacks
https://codex.wordpress.org/Data_Validation#Database
https://make.wordpress.org/core/2012/12/12/php-warning-missing-argument-2-for-wpdb-prepare/
https://ottopress.com/2013/better-know-a-vulnerability-sql-injection/
Example(s) from your plugin:
includes/snippet-db-tables-manager.php:801 $where_clause = '';
includes/snippet-db-tables-manager.php:798 $where_conditions[] = "`{$column->Field}`LIKE %s";
includes/snippet-db-tables-manager.php:803 $where_clause = 'WHERE (' . implode( ' OR ', $where_conditions ) . ')';
includes/snippet-db-tables-manager.php:826 $data_query = "SELECT * FROM`{$table}` {$where_clause} LIMIT %d, %d";
includes/snippet-db-tables-manager.php:834 $rows = $wpdb->get_results( $wpdb->prepare( $data_query, $offset, $per_page ), ARRAY_A );

# There is a call to a wpdb::prepare() function, that's correct.

# You cannot add variables like "$column->Field" directly to the SQL query.

# Using wpdb::prepare($query, $args) you will need to include placeholders for each variable within the query and include the variables in the second parameter.

# You cannot add calls like "implode(' OR ', $where_conditions)" directly to the SQL query.

includes/snippet-post-duplicator.php:124 $sql_query = "INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value) ";
includes/snippet-post-duplicator.php:146 $sql_query .= implode( ' UNION ALL ', $sql_query_sel );
includes/snippet-post-duplicator.php:149 $wpdb->query( $sql_query );

# The SQL query needs to be included in a wpdb::prepare($query, $args) function.

# Remember that you will need to include placeholders for each variable within the query and include their calls in the second parameter of wpdb::prepare().

... out of a total of 6 incidences.

Note: Passing individual values to wpdb::prepare using placeholders is fairly straightforward, but what if we need to pass an array of values instead?

You'll need to create a placeholder for each item of the array and pass all the corresponding values to those placeholders, this seems tricky, but here is a snippet to do so.
$wordcamp_id_placeholders = implode( ', ', array_fill( 0, count( $wordcamp_ids ), '%d' ) );
$prepare_values = array_merge( array( $new_status ), $wordcamp_ids );
$wpdb->query( $wpdb->prepare( "
        UPDATE `$table_name`        SET`post_status` = %s
WHERE ID IN ( $wordcamp_id_placeholders )",
$prepare_values
) );
There is a core ticket that could make this easier in the future: https://core.trac.wordpress.org/ticket/54042

Example(s) from your plugin:
includes/snippet-db-tables-manager.php:803 $where_clause = 'WHERE (' . implode( ' OR ', $where_conditions ) . ')';

👉 Continue with the review process.

Read this email thoroughly.

Please, take the time to fully understand the issues we've raised. Review the examples provided, read the relevant documentation, and research as needed. Our goal is for you to gain a clear understanding of the problems so you can address them effectively and avoid similar issues when maintaining your plugin in the future.
Note that there may be false positives - we are humans and make mistakes, we apologize if there is anything we have gotten wrong. If you have doubts you can ask us for clarification, when asking us please be clear, concise, direct and include an example.

📋 Complete your checklist.

✔️ I fixed all the issues in my plugin based on the feedback I received and my own review, as I know that the Plugins Team may not share all cases of the same issue. I am familiar with tools such as Plugin Check, PHPCS + WPCS, and similar utilities to help me identify problems in my code.
✔️ I tested my updated plugin on a clean WordPress installation with WP_DEBUG set to true.
⚠️ Do not skip this step. Testing is essential to make sure your fixes actually work and that you haven’t introduced new issues.

✔️ I acknowledge that this review will be rejected if I overlook the issues or fail to test my code.
✔️ I went to "Add your plugin" and uploaded the updated version. I can continue updating the code there throughout the review process — the team will always check the latest version.
✔️ I replied to this email. I was concise and shared any clarifications or important context that the team needed to know.
I didn't list all the changes, as the team will review the entire plugin again and that is not necessary at all.

ℹ️ To make this process as quick as possible and to avoid burden on the volunteers devoting their time to review this plugin's code, we ask you to thoroughly check all shared issues and fix them before sending the code back to us. I know we already asked you to do so, and it is because we are really trying to make it very clear.

While we try to make our reviews as exhaustive as possible we, like you, are humans and may have missed things. We appreciate your patience and understanding.
