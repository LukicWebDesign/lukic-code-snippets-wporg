/**
 * Admin JavaScript for Lukic Maintenance Mode
 */
jQuery(document).ready(function($) {
    // Initialize color pickers
    $('.Lukic-color-picker').wpColorPicker({
        change: function(event, ui) {
            // Update preview on color change
            updatePreview();
        }
    });
    
    // Media uploader for background image
    $('.Lukic-upload-image').on('click', function(e) {
        e.preventDefault();
        
        var imageField = $('#Lukic_maintenance_bg_image');
        var imagePreview = $(this).closest('.Lukic-media-field').find('.Lukic-media-preview img');
        
        // Create the media frame
        var fileFrame = wp.media.frames.fileFrame = wp.media({
            title: 'Select or Upload Background Image',
            button: {
                text: 'Use this image'
            },
            multiple: false
        });
        
        // When an image is selected, run a callback
        fileFrame.on('select', function() {
            var attachment = fileFrame.state().get('selection').first().toJSON();
            imageField.val(attachment.url);
            imagePreview.attr('src', attachment.url);
            
            // Update preview
            updatePreview();
        });
        
        // Open the media library dialog
        fileFrame.open();
    });
    
    // Reset to default background image
    $('.Lukic-reset-image').on('click', function(e) {
        e.preventDefault();
        
        var defaultImage = $(this).data('default');
        var imageField = $('#Lukic_maintenance_bg_image');
        var imagePreview = $(this).closest('.Lukic-media-field').find('.Lukic-media-preview img');
        
        imageField.val(defaultImage);
        imagePreview.attr('src', defaultImage);
        
        // Update preview
        updatePreview();
    });
    
    // Update preview on input changes
    $('#Lukic_maintenance_title, #Lukic_maintenance_subtitle, #Lukic_maintenance_message').on('input', function() {
        updatePreview();
    });
    
    // Update preview on font size changes
    $('#Lukic_maintenance_title_font_size, #Lukic_maintenance_subtitle_font_size, #Lukic_maintenance_message_font_size').on('input', function() {
        updatePreview();
    });
    
    // Initialize the preview on page load
    updatePreview();
    
    // Function to update the preview
    function updatePreview() {
        var preview = $('#Lukic-maintenance-preview');
        var title = $('#Lukic_maintenance_title').val();
        var subtitle = $('#Lukic_maintenance_subtitle').val();
        var message = $('#Lukic_maintenance_message').val();
        var bgImage = $('#Lukic_maintenance_bg_image').val();
        
        // Update content
        preview.find('.Lukic-preview-title').text(title);
        preview.find('.Lukic-preview-subtitle').text(subtitle);
        preview.find('.Lukic-preview-message').html(message);
        
        // Update background
        preview.find('.Lukic-preview-background').css('background-image', 'url(' + bgImage + ')');
        
        // Update styles
        var titleFontSize = $('#Lukic_maintenance_title_font_size').val();
        var subtitleFontSize = $('#Lukic_maintenance_subtitle_font_size').val();
        var messageFontSize = $('#Lukic_maintenance_message_font_size').val();
        
        var titleColor = $('#Lukic_maintenance_title_color').val();
        var subtitleColor = $('#Lukic_maintenance_subtitle_color').val();
        var messageColor = $('#Lukic_maintenance_message_color').val();
        var overlayColor = $('#Lukic_maintenance_overlay_color').val();
        
        preview.find('.Lukic-preview-title').css({
            'font-size': titleFontSize,
            'color': titleColor
        });
        
        preview.find('.Lukic-preview-subtitle').css({
            'font-size': subtitleFontSize,
            'color': subtitleColor
        });
        
        preview.find('.Lukic-preview-message').css({
            'font-size': messageFontSize,
            'color': messageColor
        });
        
        preview.find('.Lukic-preview-overlay').css('background-color', overlayColor);
    }
});
