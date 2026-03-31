/**
 * Lukic Database Tables Manager
 * JavaScript functionality for the DB Tables Manager snippet
 */
(function($) {
    'use strict';

    // Initialize the tables manager
    $(document).ready(function() {
        // Initialize DataTables for better table display
        if ($.fn.DataTable) {
            $('.Lukic-tables-list').DataTable({
                pageLength: 10,
                lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
                order: [[0, 'asc']], // Order by table name
                columnDefs: [
                    { orderable: false, targets: -1 } // Disable ordering on Actions column
                ]
            });
        }

        // Tab navigation
        $('.Lukic-tab-button').on('click', function() {
            const tabId = $(this).data('tab');
            
            // Update active tab button
            $('.Lukic-tab-button').removeClass('active');
            $(this).addClass('active');
            
            // Show selected tab content
            $('.Lukic-tab-content').removeClass('active');
            $('#' + tabId).addClass('active');
        });

        // View table details button (delegated event for DataTables pagination)
        $(document).on('click', '.view-table', function() {
            const tableName = $(this).data('table');
            showTableDetails(tableName);
        });

        // Modal close button
        $('.Lukic-modal-close').on('click', function() {
            $('.Lukic-modal').hide();
        });

        // Close modal when clicking outside content
        $(window).on('click', function(e) {
            if ($(e.target).hasClass('Lukic-modal')) {
                $('.Lukic-modal').hide();
            }
        });

        // Pagination buttons for table data
        $('#data .prev-page, #data .next-page').on('click', function() {
            const tableName = $('#Lukic-table-name').text();
            const currentPage = parseInt($(this).data('page') || 1);
            loadTableData(tableName, currentPage);
        });

        // Export table button
        $('.export-table').on('click', function() {
            const tableName = $('#Lukic-table-name').text();
            exportTableData(tableName);
        });

        // Edit row button (delegated event for dynamically added buttons)
        $(document).on('click', '.edit-row', function() {
            const tableName = $(this).data('table');
            const primaryKey = $(this).data('pk');
            const primaryValue = $(this).data('pk-value');
            showEditModal(tableName, primaryKey, primaryValue);
        });

        // Edit modal close buttons
        $('.Lukic-edit-close, .Lukic-edit-cancel').on('click', function() {
            $('#Lukic-edit-modal').hide();
        });

        // Edit form submission
        $('#Lukic-edit-form').on('submit', function(e) {
            e.preventDefault();
            updateTableRow();
        });

        // Search functionality
        $('.search-table').on('click', function() {
            const tableName = $('#Lukic-table-name').text();
            const searchTerm = $('#table-search').val().trim();
            searchTableData(tableName, searchTerm, 1);
        });

        // Clear search
        $('.clear-search').on('click', function() {
            $('#table-search').val('');
            $('.clear-search').hide();
            const tableName = $('#Lukic-table-name').text();
            loadTableData(tableName, 1); // Load regular data
        });

        // Search on Enter key
        $('#table-search').on('keypress', function(e) {
            if (e.which === 13) { // Enter key
                $('.search-table').click();
            }
        });

        // Show/hide clear button based on search input
        $('#table-search').on('input', function() {
            const searchTerm = $(this).val().trim();
            if (searchTerm.length > 0) {
                $('.clear-search').show();
            } else {
                $('.clear-search').hide();
            }
        });
    });

    /**
     * Show table details in modal
     */
    function showTableDetails(tableName) {
        // Set table name in modal title
        $('#Lukic-table-name').text(tableName);
        
        // Reset tabs to Structure by default
        $('.Lukic-modal .Lukic-tab-button').removeClass('active');
        $('.Lukic-modal .Lukic-tab-button[data-tab="structure"]').addClass('active');
        
        $('.Lukic-modal .Lukic-tab-content').removeClass('active');
        $('#structure').addClass('active');
        
        // Reset modal content
        $('#structure-content').html('<div class="Lukic-loading">' + LukicDBManager.strings.loading + '</div>');
        $('#data-content').html('<div class="Lukic-loading">' + LukicDBManager.strings.loading + '</div>');
        
        // Show modal
        $('#Lukic-table-modal').show();
        
        // Load table structure first, then data
        loadTableStructure(tableName, function() {
            // After structure loads successfully, load data
            setTimeout(function() {
                loadTableData(tableName, 1);
            }, 100); // Small delay to prevent conflicts
        });
        
        // Add click handler for tabs
        $('.Lukic-modal .Lukic-tab-button').off('click').on('click', function() {
            const tabId = $(this).data('tab');
            
            // Update active tab button
            $('.Lukic-modal .Lukic-tab-button').removeClass('active');
            $(this).addClass('active');
            
            // Show selected tab content
            $('.Lukic-modal .Lukic-tab-content').removeClass('active');
            $('#' + tabId).addClass('active');
            
            // If switching to data tab and it's empty or still loading, reload it
            if (tabId === 'data') {
                const dataContent = $('#data-content').html();
                if (!dataContent || dataContent.includes('Lukic-loading') || dataContent.trim() === '') {
                    console.log('Data tab is empty, reloading data for table:', tableName);
                    loadTableData(tableName, 1);
                }
            }
            
            // If switching to structure tab and it's empty or still loading, reload it
            if (tabId === 'structure') {
                const structureContent = $('#structure-content').html();
                if (!structureContent || structureContent.includes('Lukic-loading') || structureContent.trim() === '') {
                    console.log('Structure tab is empty, reloading structure for table:', tableName);
                    loadTableStructure(tableName);
                }
            }
        });
    }

    /**
     * Load table structure
     */
    function loadTableStructure(tableName, callback) {
        // Show loading state
        $('#structure-content').html('<div class="Lukic-loading">' + LukicDBManager.strings.loading + '</div>');
        
        $.ajax({
            url: LukicDBManager.ajaxUrl,
            type: 'POST',
            data: {
                action: 'Lukic_get_table_structure',
                nonce: LukicDBManager.nonce,
                table: tableName
            },
            timeout: 15000, // 15 second timeout for structure
            success: function(response) {
                if (response.success) {
                    $('#structure-content').html(response.data.html);
                    // Call callback if provided and successful
                    if (typeof callback === 'function') {
                        callback(true);
                    }
                } else {
                    $('#structure-content').html('<div class="notice notice-error"><p>' + response.data + '</p></div>');
                    if (typeof callback === 'function') {
                        callback(false);
                    }
                }
            },
            error: function(xhr, status, error) {
                let errorMessage = LukicDBManager.strings.error;
                if (status === 'timeout') {
                    errorMessage = 'Request timed out while loading table structure.';
                }
                $('#structure-content').html('<div class="notice notice-error"><p>' + errorMessage + '</p></div>');
                if (typeof callback === 'function') {
                    callback(false);
                }
            }
        });
    }

    /**
     * Load table data with pagination
     */
    function loadTableData(tableName, page) {
        // Default page is 1
        page = page || 1;
        const perPage = 20;

        // Show loading state
        $('#data-content').html('<div class="Lukic-loading">' + LukicDBManager.strings.loading + '</div>');

        $.ajax({
            url: LukicDBManager.ajaxUrl,
            type: 'POST',
            data: {
                action: 'Lukic_get_table_data',
                nonce: LukicDBManager.nonce,
                table: tableName,
                page: page,
                per_page: perPage
            },
            timeout: 30000, // 30 second timeout
            success: function(response) {
                if (response.success) {
                    $('#data-content').html(response.data.html);
                    
                    // Update pagination
                    updatePagination(response.data, page, tableName);
                } else {
                    $('#data-content').html('<div class="notice notice-error"><p>' + response.data + '</p></div>');
                }
            },
            error: function(xhr, status, error) {
                let errorMessage = LukicDBManager.strings.error;
                if (status === 'timeout') {
                    errorMessage = 'Request timed out. The table might be too large.';
                }
                $('#data-content').html('<div class="notice notice-error"><p>' + errorMessage + '</p></div>');
            }
        });
    }

    /**
     * Search table data
     */
    function searchTableData(tableName, searchTerm, page) {
        // Default page is 1
        page = page || 1;
        const perPage = 20;

        $.ajax({
            url: LukicDBManager.ajaxUrl,
            type: 'POST',
            data: {
                action: 'Lukic_search_table_data',
                nonce: LukicDBManager.nonce,
                table: tableName,
                search_term: searchTerm,
                page: page,
                per_page: perPage
            },
            success: function(response) {
                if (response.success) {
                    $('#data-content').html(response.data.html);
                    
                    // Update pagination for search results
                    updateSearchPagination(response.data, page, tableName, searchTerm);
                    
                    // Show clear button if search term exists
                    if (searchTerm && searchTerm.length > 0) {
                        $('.clear-search').show();
                    }
                } else {
                    $('#data-content').html('<div class="notice notice-error"><p>' + response.data + '</p></div>');
                }
            },
            error: function() {
                $('#data-content').html('<div class="notice notice-error"><p>' + LukicDBManager.strings.error + '</p></div>');
            }
        });
    }

    /**
     * Update pagination controls
     */
    function updatePagination(data, currentPage, tableName) {
        const $prevButton = $('.Lukic-data-pagination .prev-page');
        const $nextButton = $('.Lukic-data-pagination .next-page');
        const $paginationInfo = $('.Lukic-data-pagination .pagination-info');
        
        if (data.total === 0) {
            $prevButton.prop('disabled', true);
            $nextButton.prop('disabled', true);
            $paginationInfo.text('');
            return;
        }
        
        // Update pagination info
        $paginationInfo.text(currentPage + ' / ' + data.pages);
        
        // Update buttons and their data
        $prevButton.data('page', currentPage - 1);
        $nextButton.data('page', currentPage + 1);
        
        // Disable/enable buttons
        $prevButton.prop('disabled', currentPage <= 1);
        $nextButton.prop('disabled', currentPage >= data.pages);
        
        // Re-bind click events
        $prevButton.off('click').on('click', function() {
            if (currentPage > 1) {
                loadTableData(tableName, currentPage - 1);
            }
        });
        
        $nextButton.off('click').on('click', function() {
            if (currentPage < data.pages) {
                loadTableData(tableName, currentPage + 1);
            }
        });
    }

    /**
     * Update pagination controls for search results
     */
    function updateSearchPagination(data, currentPage, tableName, searchTerm) {
        const $prevButton = $('.Lukic-data-pagination .prev-page');
        const $nextButton = $('.Lukic-data-pagination .next-page');
        const $paginationInfo = $('.Lukic-data-pagination .pagination-info');
        
        if (data.total === 0) {
            $prevButton.prop('disabled', true);
            $nextButton.prop('disabled', true);
            $paginationInfo.text('');
            return;
        }
        
        // Update pagination info
        $paginationInfo.text(currentPage + ' / ' + data.pages + ' (search results)');
        
        // Update buttons and their data
        $prevButton.data('page', currentPage - 1);
        $nextButton.data('page', currentPage + 1);
        
        // Disable/enable buttons
        $prevButton.prop('disabled', currentPage <= 1);
        $nextButton.prop('disabled', currentPage >= data.pages);
        
        // Re-bind click events for search pagination
        $prevButton.off('click').on('click', function() {
            if (currentPage > 1) {
                searchTableData(tableName, searchTerm, currentPage - 1);
            }
        });
        
        $nextButton.off('click').on('click', function() {
            if (currentPage < data.pages) {
                searchTableData(tableName, searchTerm, currentPage + 1);
            }
        });
    }

    /**
     * Export table data to CSV
     */
    function exportTableData(tableName) {
        $.ajax({
            url: LukicDBManager.ajaxUrl,
            type: 'POST',
            data: {
                action: 'Lukic_export_table',
                nonce: LukicDBManager.nonce,
                table: tableName
            },
            success: function(response) {
                if (response.success) {
                    // Create CSV file download
                    const blob = new Blob([response.data.content], { type: 'text/csv;charset=utf-8;' });
                    const url = URL.createObjectURL(blob);
                    const link = document.createElement('a');
                    
                    link.href = url;
                    link.download = response.data.filename;
                    link.style.display = 'none';
                    
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    
                    // Show success message
                    alert(LukicDBManager.strings.exportSuccess);
                } else {
                    alert(response.data);
                }
            },
            error: function() {
                alert(LukicDBManager.strings.error);
            }
        });
    }

    /**
     * Show edit modal for a table row
     */
    function showEditModal(tableName, primaryKey, primaryValue) {
        // Show loading in modal
        $('#edit-form-fields').html('<div class="Lukic-loading">' + LukicDBManager.strings.loading + '</div>');
        $('#Lukic-edit-modal').show();

        // Load row data
        $.ajax({
            url: LukicDBManager.ajaxUrl,
            type: 'POST',
            data: {
                action: 'Lukic_get_table_row',
                nonce: LukicDBManager.nonce,
                table: tableName,
                primary_key: primaryKey,
                primary_value: primaryValue
            },
            success: function(response) {
                if (response.success) {
                    buildEditForm(response.data, tableName, primaryKey, primaryValue);
                } else {
                    $('#edit-form-fields').html('<div class="notice notice-error"><p>' + response.data + '</p></div>');
                }
            },
            error: function() {
                $('#edit-form-fields').html('<div class="notice notice-error"><p>' + LukicDBManager.strings.error + '</p></div>');
            }
        });
    }

    /**
     * Build the edit form with row data
     */
    function buildEditForm(data, tableName, primaryKey, primaryValue) {
        const row = data.row;
        const columns = data.columns;
        
        let formHTML = '';
        formHTML += '<input type="hidden" id="edit-table" value="' + tableName + '">';
        formHTML += '<input type="hidden" id="edit-primary-key" value="' + primaryKey + '">';
        formHTML += '<input type="hidden" id="edit-primary-value" value="' + primaryValue + '">';

        columns.forEach(function(column) {
            const fieldName = column.Field;
            const fieldType = column.Type;
            const isNullable = column.Null === 'YES';
            const hasDefault = column.Default !== null;
            const isAutoIncrement = column.Extra && column.Extra.includes('auto_increment');
            const isPrimaryKey = column.Key === 'PRI';
            
            // A field strictly requires user input ONLY if it cannot be null, has no default, and doesn't auto-increment
            const isStrictlyRequired = !isNullable && !hasDefault && !isAutoIncrement && !isPrimaryKey;
            
            const currentValue = row[fieldName] || '';

            formHTML += '<div class="Lukic-field-group">';
            formHTML += '<label for="field-' + fieldName + '">' + fieldName;
            
            if (isPrimaryKey) {
                formHTML += ' <em>(Primary Key)</em>';
            }
            if (isStrictlyRequired) {
                formHTML += ' <span style="color: red;">*</span>';
            }
            
            formHTML += '</label>';

            if (isPrimaryKey) {
                // Primary key should be read-only
                formHTML += '<input type="text" id="field-' + fieldName + '" value="' + currentValue + '" readonly style="background-color: #f5f5f5;">';
            } else if (fieldType.includes('text') || fieldType.includes('blob')) {
                // Use textarea for text/blob fields
                formHTML += '<textarea id="field-' + fieldName + '" name="' + fieldName + '" ' + (isStrictlyRequired ? 'required' : '') + '>' + currentValue + '</textarea>';
            } else {
                // Use input for other fields
                formHTML += '<input type="text" id="field-' + fieldName + '" name="' + fieldName + '" value="' + currentValue + '" ' + (isStrictlyRequired ? 'required' : '') + '>';
            }

            if (isNullable || hasDefault || isAutoIncrement) {
                let hintText = [];
                if (isNullable) hintText.push('NULL');
                if (hasDefault) hintText.push('Default');
                if (isAutoIncrement) hintText.push('Auto-increment');
                
                formHTML += '<small style="color: #666;">Leave empty for ' + hintText.join(' / ') + '</small>';
            }

            formHTML += '</div>';
        });

        $('#edit-form-fields').html(formHTML);
    }

    /**
     * Update table row with form data
     */
    function updateTableRow() {
        const tableName = $('#edit-table').val();
        const primaryKey = $('#edit-primary-key').val();
        const primaryValue = $('#edit-primary-value').val();
        
        // Collect form data
        const rowData = {};
        $('#edit-form-fields input[name], #edit-form-fields textarea[name]').each(function() {
            const fieldName = $(this).attr('name');
            const fieldValue = $(this).val();
            rowData[fieldName] = fieldValue;
        });

        // Show loading state
        $('.Lukic-edit-actions button').prop('disabled', true);

        $.ajax({
            url: LukicDBManager.ajaxUrl,
            type: 'POST',
            data: {
                action: 'Lukic_update_table_row',
                nonce: LukicDBManager.nonce,
                table: tableName,
                primary_key: primaryKey,
                primary_value: primaryValue,
                row_data: rowData
            },
            success: function(response) {
                $('.Lukic-edit-actions button').prop('disabled', false);
                
                if (response.success) {
                    alert(response.data);
                    $('#Lukic-edit-modal').hide();
                    
                    // Reload table data to show changes
                    const currentTableName = $('#Lukic-table-name').text();
                    if (currentTableName === tableName) {
                        loadTableData(tableName, 1);
                    }
                } else {
                    alert('Error: ' + response.data);
                }
            },
            error: function() {
                $('.Lukic-edit-actions button').prop('disabled', false);
                alert(LukicDBManager.strings.error);
            }
        });
    }
})(jQuery);
