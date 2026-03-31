/**
 * Lukic Redirect Manager
 * JavaScript functionality for the redirect manager admin interface
 */

jQuery(document).ready(function ($) {
    // Initialize tabs system
    initTabs();

    // Setup search and filtering
    setupSearch();

    /**
     * Initialize tab navigation
     */
    function initTabs() {
        // Hide all tab content by default
        $('.tab-content').hide();
        // Show the first tab
        $('#tab-redirects').show();

        // Handle tab clicks
        $('.nav-tab').on('click', function (e) {
            e.preventDefault();

            // Remove active class from all tabs
            $('.nav-tab').removeClass('nav-tab-active');
            // Add active class to clicked tab
            $(this).addClass('nav-tab-active');

            // Hide all tab content
            $('.tab-content').hide();
            // Show the tab content for the clicked tab
            $($(this).attr('href')).show();
        });
    }

    // Initialize dialog for editing redirects
    const editDialog = $('#edit-redirect-dialog').dialog({
        autoOpen: false,
        modal: true,
        width: 500,
        buttons: {
            "Save Changes": function () {
                saveRedirect('edit');
                $(this).dialog("close");
            },
            Cancel: function () {
                $(this).dialog("close");
            }
        },
        close: function () {
            $('#edit-redirect-form')[0].reset();
        }
    });

    // Handle form submission for adding new redirects
    $('#add-redirect-form').on('submit', function (e) {
        e.preventDefault();
        saveRedirect('add');
    });

    // Handle edit button clicks
    $(document).on('click', '.edit-redirect', function (e) {
        e.preventDefault();

        // Get the redirect data from the table row
        const row = $(this).closest('tr');
        const redirectId = row.data('id');
        const sourceUrl = row.find('td:eq(0)').text();
        const targetUrl = row.find('td:eq(1)').text();
        const redirectType = row.find('td:eq(2)').text();
        const patternMatch = row.find('td:eq(3)').find('.dashicons-yes').length > 0 ? 1 : 0;
        const status = row.find('td:eq(4)').text() === 'Active' ? 1 : 0;

        // Populate the edit form
        $('#edit_redirect_id').val(redirectId);
        $('#edit_source_url').val(sourceUrl);
        $('#edit_target_url').val(targetUrl);
        $('#edit_redirect_type').val(redirectType);
        $('#edit_pattern_match').prop('checked', patternMatch === 1);
        $('#edit_status').val(status);

        // Open the edit dialog
        editDialog.dialog('open');
    });

    /**
     * Setup search and filtering functionality
     */
    function setupSearch() {
        // Search functionality
        $('#redirect-search').on('keyup', function () {
            filterRedirects();
        });

        // Filter button click
        $('#redirect-filter-button').on('click', function (e) {
            e.preventDefault();
            filterRedirects();
        });

        // Filter on select change
        $('#redirect-filter-type, #redirect-filter-status, #redirect-filter-pattern').on('change', function () {
            filterRedirects();
        });
    }

    /**
     * Filter redirects based on search and filter inputs
     */
    function filterRedirects() {
        const searchTerm = $('#redirect-search').val().toLowerCase();
        const typeFilter = $('#redirect-filter-type').val();
        const statusFilter = $('#redirect-filter-status').val();
        const patternFilter = $('#redirect-filter-pattern').val();

        // Loop through all rows and hide/show based on filters
        $('#tab-redirects table tbody tr').each(function () {
            const row = $(this);
            const sourceUrl = row.find('td:eq(0)').text().toLowerCase();
            const targetUrl = row.find('td:eq(1)').text().toLowerCase();
            const type = row.find('td:eq(2)').text();
            const hasPattern = row.find('td:eq(3)').find('.dashicons-yes').length > 0;
            const isActive = row.find('td:eq(4)').text() === 'Active';

            // Apply search filter
            const matchesSearch = searchTerm === '' ||
                sourceUrl.includes(searchTerm) ||
                targetUrl.includes(searchTerm);

            // Apply type filter
            const matchesType = typeFilter === '' || type.includes(typeFilter);

            // Apply status filter
            const matchesStatus = statusFilter === '' ||
                (statusFilter === 'active' && isActive) ||
                (statusFilter === 'inactive' && !isActive);

            // Apply pattern filter
            const matchesPattern = patternFilter === '' ||
                (patternFilter === 'pattern' && hasPattern) ||
                (patternFilter === 'exact' && !hasPattern);

            // Show/hide row based on all filters
            if (matchesSearch && matchesType && matchesStatus && matchesPattern) {
                row.show();
            } else {
                row.hide();
            }
        });

        // Show a message if no results found
        const visibleRows = $('#tab-redirects table tbody tr:visible').length;
        if (visibleRows === 0 && $('#tab-redirects table tbody tr').length > 0) {
            if ($('#no-results-message').length === 0) {
                $('#tab-redirects table').after(
                    '<div id="no-results-message" class="Lukic-empty-state" style="margin-top: 20px;">' +
                    '<p>' + 'No redirects match your search criteria.' + '</p>' +
                    '<button id="clear-filters" class="button">' + 'Clear Filters' + '</button>' +
                    '</div>'
                );

                // Add clear filters button functionality
                $('#clear-filters').on('click', function () {
                    $('#redirect-search').val('');
                    $('#redirect-filter-type, #redirect-filter-status, #redirect-filter-pattern').val('');
                    filterRedirects();
                });
            }
        } else {
            $('#no-results-message').remove();
        }
    }

    // Handle delete button clicks
    $(document).on('click', '.delete-redirect', function (e) {
        e.preventDefault();

        if (confirm(Lukic_redirect_vars.confirm_delete)) {
            const row = $(this).closest('tr');
            const redirectId = row.data('id');

            // Send AJAX request to delete the redirect
            $.ajax({
                url: Lukic_redirect_vars.ajax_url,
                type: 'POST',
                data: {
                    action: 'Lukic_delete_redirect',
                    redirect_id: redirectId,
                    nonce: Lukic_redirect_vars.nonce
                },
                success: function (response) {
                    if (response.success) {
                        // Remove the row from the table
                        row.fadeOut(300, function () {
                            $(this).remove();

                            // Show "no redirects" message if table is empty
                            if ($('#tab-redirects table tbody tr').length === 0) {
                                $('#tab-redirects .Lukic-table-container').html(
                                    '<p>' + 'No redirects found. Add your first redirect using the "Add New" tab.' + '</p>'
                                );
                            }
                        });

                        // Show success message
                        showMessage(response.data.message, 'success');
                    } else {
                        // Show error message
                        showMessage(response.data.message, 'error');
                    }
                }
            });
        }
    });

    // Handle settings form submission
    $('#redirect-settings-form').on('submit', function (e) {
        e.preventDefault();

        const trackHits = $('#track_hits').is(':checked') ? 1 : 0;
        const logLastAccess = $('#log_last_access').is(':checked') ? 1 : 0;

        // Send AJAX request to save settings
        $.ajax({
            url: Lukic_redirect_vars.ajax_url,
            type: 'POST',
            data: {
                action: 'Lukic_save_redirect_settings',
                track_hits: trackHits,
                log_last_access: logLastAccess,
                nonce: $('#redirect_settings_nonce').val()
            },
            success: function (response) {
                if (response.success) {
                    showMessage(response.data.message, 'success');
                } else {
                    showMessage(response.data.message, 'error');
                }
            }
        });
    });

    /**
     * Save a redirect (add new or edit existing)
     */
    function saveRedirect(mode) {
        // Get form data based on mode
        let redirectId, form, sourceUrl, targetUrl, redirectType, patternMatch, status;

        if (mode === 'add') {
            form = $('#add-redirect-form');
            redirectId = 0;
            sourceUrl = $('#source_url').val();
            targetUrl = $('#target_url').val();
            redirectType = $('#redirect_type').val();
            patternMatch = $('#pattern_match').is(':checked') ? 1 : 0;
            status = $('#status').val();
        } else {
            form = $('#edit-redirect-form');
            redirectId = $('#edit_redirect_id').val();
            sourceUrl = $('#edit_source_url').val();
            targetUrl = $('#edit_target_url').val();
            redirectType = $('#edit_redirect_type').val();
            patternMatch = $('#edit_pattern_match').is(':checked') ? 1 : 0;
            status = $('#edit_status').val();
        }

        // Send AJAX request to save the redirect
        $.ajax({
            url: Lukic_redirect_vars.ajax_url,
            type: 'POST',
            data: {
                action: 'Lukic_save_redirect',
                redirect_id: redirectId,
                source_url: sourceUrl,
                target_url: targetUrl,
                redirect_type: redirectType,
                pattern_match: patternMatch,
                status: status,
                nonce: Lukic_redirect_vars.nonce
            },
            success: function (response) {
                if (response.success) {
                    // Show success message
                    showMessage(response.data.message, 'success');

                    // Reset the form if adding a new redirect
                    if (mode === 'add') {
                        $('#add-redirect-form')[0].reset();
                    }

                    // Refresh the redirects table or add the new row
                    if (mode === 'add') {
                        // Check if we need to replace the "no redirects" message
                        if ($('#tab-redirects .Lukic-table-container p').length > 0) {
                            $('#tab-redirects .Lukic-table-container').html(
                                '<table class="wp-list-table widefat fixed striped">' +
                                '<thead>' +
                                '<tr>' +
                                '<th>Source URL</th>' +
                                '<th>Target URL</th>' +
                                '<th>Type</th>' +
                                '<th>Pattern</th>' +
                                '<th>Status</th>' +
                                '<th>Hits</th>' +
                                '<th>Last Accessed</th>' +
                                '<th>Actions</th>' +
                                '</tr>' +
                                '</thead>' +
                                '<tbody></tbody>' +
                                '</table>'
                            );
                        }

                        // Add the new row to the table
                        const newRow = $('<tr data-id="' + response.data.redirect.id + '">' +
                            '<td>' + response.data.redirect.source_url + '</td>' +
                            '<td>' + response.data.redirect.target_url + '</td>' +
                            '<td>' + response.data.redirect.redirect_type + '</td>' +
                            '<td>' + (patternMatch == 1 ? '<span style="color: #00E1AF;"><span class="dashicons dashicons-yes"></span></span>' : '—') + '</td>' +
                            '<td>' + (response.data.redirect.status == 1 ? 'Active' : 'Inactive') + '</td>' +
                            '<td>0</td>' +
                            '<td>Never</td>' +
                            '<td>' +
                            '<a href="#" class="edit-redirect lcs-edit-btn">Edit</a> ' +
                            '<a href="#" class="delete-redirect lcs-delete-btn">Delete</a>' +
                            '</td>' +
                            '</tr>');

                        $('#tab-redirects table tbody').prepend(newRow);
                        newRow.hide().fadeIn(300);

                        // Switch to the redirects tab
                        $('.nav-tab[href="#tab-redirects"]').trigger('click');
                    } else {
                        // Update the existing row
                        const row = $('tr[data-id="' + redirectId + '"]');
                        row.find('td:eq(0)').text(sourceUrl);
                        row.find('td:eq(1)').text(targetUrl);
                        row.find('td:eq(2)').text(redirectType);
                        row.find('td:eq(3)').html(patternMatch == 1 ? '<span style="color: #00E1AF;"><span class="dashicons dashicons-yes"></span></span>' : '—');
                        row.find('td:eq(4)').text(status == 1 ? 'Active' : 'Inactive');
                    }
                } else {
                    // Show error message
                    showMessage(response.data.message, 'error');
                }
            }
        });
    }

    /**
     * Display a message to the user
     */
    function showMessage(message, type) {
        // Remove any existing message
        $('.Lukic-message').remove();

        // Create the message element
        const messageEl = $('<div class="Lukic-message notice ' + (type === 'success' ? 'notice-success' : 'notice-error') + ' is-dismissible"><p>' + message + '</p></div>');

        // Add the message to the page
        $('.wpl-code-snippets-header').after(messageEl);

        // Make the message dismissible
        messageEl.append('<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>');
        messageEl.find('.notice-dismiss').on('click', function () {
            $(this).parent().fadeOut(300, function () {
                $(this).remove();
            });
        });

        // Automatically remove the message after 5 seconds
        setTimeout(function () {
            messageEl.fadeOut(300, function () {
                $(this).remove();
            });
        }, 5000);
    }
});
