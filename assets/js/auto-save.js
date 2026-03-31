/**
 * Auto-Save functionality for Lukic Code Snippets
 * 
 * Handles automatic saving of snippet toggles when switches are changed
 */

(function ($) {
    'use strict';

    $(document).ready(function () {

        // Script initialization

        // Auto-save snippet toggles
        $('.Lukic-switch input[type="checkbox"]').on('change', function () {
            const $checkbox = $(this);
            const $switch = $checkbox.closest('.Lukic-switch');
            const $snippet = $checkbox.closest('.Lukic-snippet');
            const snippetName = $snippet.find('h3').text();
            const isChecked = $checkbox.is(':checked');

            // Process toggle change

            // Show loading state
            $switch.addClass('loading');

            // Get all current form values
            const currentOptions = {};
            $('input[name^="Lukic_snippet_codes_options["]').each(function () {
                const name = $(this).attr('name');
                const match = name.match(/Lukic_snippet_codes_options\[(.+?)\]/);
                if (match) {
                    const snippetId = match[1];
                    currentOptions[snippetId] = $(this).is(':checked') ? '1' : '0';
                }
            });

            // AJAX call to save
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'Lukic_auto_save_snippet',
                    nonce: Lukic_auto_save.nonce,
                    options: JSON.stringify(currentOptions)
                },
                success: function (response) {
                    $switch.removeClass('loading');

                    if (response.success) {
                        // Update snippet visual state
                        if (isChecked) {
                            $snippet.addClass('snippet-active');
                        } else {
                            $snippet.removeClass('snippet-active');
                        }

                        // Update the "Active" count in the header stat live
                        updateActiveCount();

                        // Check if this snippet requires a page refresh
                        if (needsPageRefresh($checkbox)) {
                            // Show refresh notification and auto-refresh
                            // Make sure status text is defined
                            const statusText = isChecked ?
                                (typeof Lukic_auto_save.activated !== 'undefined' ? Lukic_auto_save.activated : ' Activated') :
                                (typeof Lukic_auto_save.deactivated !== 'undefined' ? Lukic_auto_save.deactivated : 'Deactivated');

                            // Trim snippet name to remove any extra whitespace
                            const trimmedName = snippetName.trim();
                            showRefreshNotification('<strong>' + trimmedName + '</strong>&nbsp;&nbsp;' + statusText);

                            // Auto-refresh after 1 second (faster)
                            setTimeout(function () {
                                window.location.reload();
                            }, 500);
                        } else {
                            // Show regular success feedback
                            // Make sure status text is defined
                            const statusText = isChecked ?
                                (typeof Lukic_auto_save.activated !== 'undefined' ? Lukic_auto_save.activated : ' Activated') :
                                (typeof Lukic_auto_save.deactivated !== 'undefined' ? Lukic_auto_save.deactivated : 'Deactivated');

                            // Trim snippet name to remove any extra whitespace
                            const trimmedName = snippetName.trim();
                            showNotification('<strong>' + trimmedName + '</strong>&nbsp;&nbsp;' + statusText, 'success');
                        }
                    } else {
                        // Revert checkbox state on error
                        $checkbox.prop('checked', !isChecked);
                        const errorMsg = typeof Lukic_auto_save.error_saving === 'undefined' ? 'Error saving settings' : Lukic_auto_save.error_saving;
                        showNotification(errorMsg, 'error');
                    }
                },
                error: function () {
                    $switch.removeClass('loading');
                    // Revert checkbox state on error
                    $checkbox.prop('checked', !isChecked);
                    const errorMsg = typeof Lukic_auto_save.error_saving === 'undefined' ? 'Error saving settings' : Lukic_auto_save.error_saving;
                    showNotification(errorMsg, 'error');
                }
            });
        });

        // Function to update the "Active" count in the header stat
        function updateActiveCount() {
            var activeCount = $('input[name^="Lukic_snippet_codes_options["]:checked').length;
            // The first stat count in the header is always the "Active" count
            $('.wpl-code-snippets-header__stats-item-count').first().text(activeCount);
        }

        // Function to check if snippet needs page refresh
        function needsPageRefresh($checkbox) {
            const name = $checkbox.attr('name');
            const match = name.match(/Lukic_snippet_codes_options\[(.+?)\]/);
            if (!match) {
                return false;
            }
            const snippetId = match[1];

            // List of snippets that affect admin interface and need refresh
            const refreshRequired = [
                'site_visibility',              // Admin Bar Site Visibility Indicator
                'limit_revisions',              // Limit Revisions (affects admin menu)
                'wider_admin_menu',             // Wider Admin Menu
                'clean_dashboard',              // Clean Dashboard (affects widgets)
                'hide_admin_bar',               // Hide Admin Bar on Frontend
                'hide_admin_notices',           // Hide Admin Notices (affects admin interface)
                'custom_admin_footer',          // Custom Admin Footer Text (affects admin footer)
                'hide_footer_thankyou',         // Hide Footer Thank You (affects admin footer)
                'maintenance_mode',             // Maintenance Mode (affects site behavior)
                'security_headers',             // Security Headers Manager (affects headers)
                'word_counter',                 // Word Counter (adds meta box to post editor)
                'content_order',                // Content Order (affects post/page ordering)
                'show_acf_columns',             // Show ACF Fields in Admin Tables (affects admin columns)
                'fluid_typography',             // Fluid Typography Calculator (adds admin tools)
                'meta_tags_editor',             // Meta Tags Editor (adds admin interface)
                'redirect_manager',             // Redirect Manager (adds admin interface)
                'image_attributes_editor',      // Image Attributes Editor (adds admin interface)
                'db_tables_manager',            // Custom Database Tables Manager (adds admin interface)
                'media_replace',                // Media Replace (adds admin interface)
                'disable_comments',             // Disable Comments (removes admin menu)
                'admin_menu_organizer',          // Admin Menu Organizer (adds admin menu)
                'login_page_designer'           // Login Page Designer (styles login page)
            ];

            return refreshRequired.includes(snippetId);
        }

        // Function to show refresh notifications
        function showRefreshNotification(message) {
            // Make sure the refreshing_message is defined
            const refreshMsg = typeof Lukic_auto_save.refreshing_message === 'undefined' ? '' : Lukic_auto_save.refreshing_message;

            const $notification = $('<div class="notice notice-info is-dismissible Lukic-auto-save-notice Lukic-refresh-notice">' +
                '<p><span class="dashicons dashicons-update-alt" style="color: #00E1AF; animation: Lukic-spin 1s linear infinite; margin-right: 8px;"></span>' +
                message + ' <span class="refresh-separator">•</span> ' + refreshMsg + '</p>' +
                '</div>');

            // Remove existing notifications
            $('.Lukic-auto-save-notice').remove();

            // Add new notification after header
            $('.wpl-code-snippets-header').after($notification);

        }

        // Function to show notifications
        function showNotification(message, type) {
            const notificationClass = type === 'success' ? 'notice-success' : 'notice-error';
            const icon = type === 'success' ? 'yes-alt' : 'warning';
            const $notification = $('<div class="notice ' + notificationClass + ' is-dismissible Lukic-auto-save-notice"><p>' +
                '<span class="dashicons dashicons-' + icon + '" style="margin-right: 8px;"></span>' +
                message + '</p></div>');

            // Remove existing auto-save notifications
            $('.Lukic-auto-save-notice').remove();

            // Add new notification after header
            $('.wpl-code-snippets-header').after($notification);

            // Auto-remove after 3 seconds
            setTimeout(function () {
                $notification.fadeOut(300, function () {
                    $(this).remove();
                });
            }, 3000);
        }

        // Add visual indicator to active snippets on page load
        $('input[name^="Lukic_snippet_codes_options["]:checked').each(function () {
            $(this).closest('.Lukic-snippet').addClass('snippet-active');
        });
    });

})(jQuery);
