jQuery(document).ready(function($) {
    // Initialize DataTables
    var metaTagsTable = $('#Lukic-meta-tags-table').DataTable({
        responsive: true,
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        columnDefs: [
            { orderable: false, targets: 4 } // Disable sorting on Actions column
        ],
        order: [[1, 'asc']], // Sort by URL by default
        language: {
            search: "_INPUT_",
            searchPlaceholder: "Search URLs, titles, descriptions..."
        }
    });

    // Make table cells editable on click
    $(document).on('click', '.Lukic-edit-btn', function(e) {
        e.preventDefault();
        
        var row = $(this).closest('tr');
        
        // Toggle edit mode for title
        var titleCell = row.find('.Lukic-editable[data-field="title"]');
        titleCell.find('.Lukic-text').hide();
        titleCell.find('.Lukic-input').show().focus();
        
        // Toggle edit mode for description
        var descCell = row.find('.Lukic-editable[data-field="description"]');
        descCell.find('.Lukic-text').hide();
        descCell.find('.Lukic-input').show();
        
        // Change button text
        $(this).text('Save').removeClass('Lukic-edit-btn').addClass('Lukic-save-btn');
    });
    
    // Save edited meta tags
    $(document).on('click', '.Lukic-save-btn', function(e) {
        e.preventDefault();
        
        var row = $(this).closest('tr');
        var id = row.data('id');
        var type = row.data('type');
        var saveButton = $(this);
        
        // Collect field values
        var newTitle = row.find('.Lukic-editable[data-field="title"] .Lukic-input').val();
        var newDesc = row.find('.Lukic-editable[data-field="description"] .Lukic-input').val();
        
        // Update title
        updateMetaTag(id, type, 'title', newTitle, function(success) {
            if (success) {
                row.find('.Lukic-editable[data-field="title"] .Lukic-text').text(newTitle);
            }
        });
        
        // Update description
        updateMetaTag(id, type, 'description', newDesc, function(success) {
            if (success) {
                row.find('.Lukic-editable[data-field="description"] .Lukic-text').text(newDesc);
            }
        });
        
        // Return to view mode
        row.find('.Lukic-editable .Lukic-text').show();
        row.find('.Lukic-editable .Lukic-input').hide();
        
        // Reset button text
        saveButton.text('Edit').removeClass('Lukic-save-btn').addClass('Lukic-edit-btn');
    });
    
    // Also handle clicks on text spans to edit individual fields
    $(document).on('click', '.Lukic-editable .Lukic-text', function() {
        var editable = $(this).closest('.Lukic-editable');
        $(this).hide();
        editable.find('.Lukic-input').show().focus();
    });
    
    // Handle the blur event on input fields to save
    $(document).on('blur', '.Lukic-editable .Lukic-input', function() {
        var editable = $(this).closest('.Lukic-editable');
        var row = editable.closest('tr');
        var id = row.data('id');
        var type = row.data('type');
        var field = editable.data('field');
        var value = $(this).val();
        
        if (row.find('.Lukic-save-btn').length > 0) {
            // Don't save on blur if we're in edit mode with a save button
            return;
        }
        
        updateMetaTag(id, type, field, value, function(success) {
            if (success) {
                editable.find('.Lukic-text').text(value);
            }
            editable.find('.Lukic-text').show();
            editable.find('.Lukic-input').hide();
        });
    });
    
    // CSV Export functionality
    $('#export-meta-tags').on('click', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: LukicMetaTagsEditor.ajaxurl,
            type: 'POST',
            data: {
                action: 'Lukic_export_meta_tags_csv',
                nonce: LukicMetaTagsEditor.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Create a Blob with the CSV content
                    var blob = new Blob([response.data.content], { type: 'text/csv;charset=utf-8;' });
                    
                    // Create a download link
                    var link = document.createElement('a');
                    var url = URL.createObjectURL(blob);
                    link.setAttribute('href', url);
                    link.setAttribute('download', response.data.filename);
                    link.style.visibility = 'hidden';
                    
                    // Add to document, trigger click and remove
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    
                    // Show success notice
                    showNotice('success', 'CSV file exported successfully.');
                } else {
                    // Show error notice
                    showNotice('error', response.data.message);
                }
            },
            error: function() {
                // Show error notice
                showNotice('error', 'Connection error. Please try again.');
            }
        });
    });
    
    // Show import form when import button is clicked
    $('#import-meta-tags-btn').on('click', function(e) {
        e.preventDefault();
        $('#import-meta-tags-form').toggle();
    });
    
    // Handle CSV import
    $('#import-meta-tags-form').on('submit', function(e) {
        e.preventDefault();
        
        var fileInput = $('#import-meta-tags-file')[0];
        
        // Check if a file was selected
        if (fileInput.files.length === 0) {
            showNotice('error', 'Please select a CSV file to import.');
            return;
        }
        
        // Check file extension
        var fileName = fileInput.files[0].name;
        var fileExt = fileName.split('.').pop().toLowerCase();
        
        if (fileExt !== 'csv') {
            showNotice('error', 'Please select a valid CSV file.');
            return;
        }
        
        // Create FormData object
        var formData = new FormData();
        formData.append('action', 'Lukic_import_meta_tags_csv');
        formData.append('nonce', LukicMetaTagsEditor.nonce);
        formData.append('file', fileInput.files[0]);
        
        // Show loading message
        showNotice('info', 'Uploading and processing CSV file...');
        
        // Send AJAX request
        $.ajax({
            url: LukicMetaTagsEditor.ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    // Show success notice
                    showNotice('success', response.data.message);
                    
                    // Reset form
                    $('#import-meta-tags-form')[0].reset();
                    $('#import-meta-tags-form').hide();
                    
                    // Reload the page to show updated data
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    // Show error notice
                    showNotice('error', response.data.message);
                    
                    // Show detailed errors if any
                    if (response.data.errors && response.data.errors.length > 0) {
                        console.log('Import errors:', response.data.errors);
                    }
                }
            },
            error: function() {
                // Show error notice
                showNotice('error', 'Connection error. Please try again.');
            }
        });
    });
    
    // Function to update meta tag via AJAX
    function updateMetaTag(id, type, field, value, callback) {
        $.ajax({
            url: LukicMetaTagsEditor.ajaxurl,
            type: 'POST',
            data: {
                action: 'Lukic_update_meta_tag',
                nonce: LukicMetaTagsEditor.nonce,
                id: id,
                type: type,
                field: field,
                value: value
            },
            success: function(response) {
                if (response.success) {
                    if (typeof callback === 'function') {
                        callback(true);
                    }
                    
                    // Show success notice
                    showNotice('success', response.data.message);
                } else {
                    if (typeof callback === 'function') {
                        callback(false);
                    }
                    
                    // Show error notice
                    showNotice('error', response.data.message);
                }
            },
            error: function() {
                if (typeof callback === 'function') {
                    callback(false);
                }
                
                // Show error notice
                showNotice('error', 'Connection error. Please try again.');
            }
        });
    }
    
    // Function to show notices
    function showNotice(type, message) {
        // Remove existing notices
        $('.Lukic-ajax-notice').remove();
        
        // Create new notice
        var notice = $('<div class="Lukic-ajax-notice Lukic-notice Lukic-notice-' + type + '">' + message + '</div>');
        $('.Lukic-card-header').append(notice);
        
        // Automatically remove notice after 3 seconds
        setTimeout(function() {
            notice.fadeOut(300, function() {
                $(this).remove();
            });
        }, 3000);
    }
});
