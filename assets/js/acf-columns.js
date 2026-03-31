/**
 * Lukic Snippet Codes - ACF Columns
 * JavaScript for the ACF columns settings page
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        // Toggle sections
        $('.Lukic-acf-field-group h4').on('click', function() {
            $(this).next('.Lukic-checkbox-group').slideToggle('fast');
            $(this).toggleClass('collapsed');
        });
        
        // Search functionality for fields
        $('#acf-field-search').on('input', function() {
            const searchTerm = $(this).val().toLowerCase();
            
            if (searchTerm.length > 2) {
                $('.Lukic-checkbox-label').each(function() {
                    const labelText = $(this).text().toLowerCase();
                    if (labelText.indexOf(searchTerm) > -1) {
                        $(this).show();
                        // Ensure parent group is visible
                        $(this).closest('.Lukic-acf-field-group').show();
                    } else {
                        $(this).hide();
                    }
                });
            } else if (searchTerm.length === 0) {
                // Show all when search is cleared
                $('.Lukic-checkbox-label, .Lukic-acf-field-group').show();
            }
        });
        
        // Filter by field type
        $('#acf-field-type-filter').on('change', function() {
            const selectedType = $(this).val();
            
            if (selectedType === 'all') {
                $('.Lukic-checkbox-label, .Lukic-acf-field-group').show();
            } else {
                $('.Lukic-checkbox-label').each(function() {
                    const fieldType = $(this).find('input').data('field-type');
                    if (fieldType === selectedType) {
                        $(this).show();
                        // Ensure parent group is visible
                        $(this).closest('.Lukic-acf-field-group').show();
                    } else {
                        $(this).hide();
                    }
                });
                
                // Hide empty groups
                $('.Lukic-acf-field-group').each(function() {
                    const visibleFields = $(this).find('.Lukic-checkbox-label:visible').length;
                    if (visibleFields === 0) {
                        $(this).hide();
                    }
                });
            }
        });
        
        // Select/deselect all checkboxes in a group
        $('.Lukic-select-all').on('click', function(e) {
            e.preventDefault();
            
            const $group = $(this).closest('.Lukic-acf-field-group');
            const $checkboxes = $group.find('input[type="checkbox"]');
            
            if ($(this).data('state') === 'select') {
                $checkboxes.prop('checked', true);
                $(this).data('state', 'deselect').text('Deselect All');
            } else {
                $checkboxes.prop('checked', false);
                $(this).data('state', 'select').text('Select All');
            }
        });
        
        // Add field type info tooltip
        $('.field-type').hover(function() {
            const fieldType = $(this).text().replace(/[()]/g, '');
            const supportInfo = getFieldTypeSupportInfo(fieldType);
            
            $('<div class="field-type-tooltip">' + supportInfo + '</div>')
                .appendTo('body')
                .css({
                    top: $(this).offset().top + 25,
                    left: $(this).offset().left
                });
        }, function() {
            $('.field-type-tooltip').remove();
        });
        
        // Helper function to get support info for field types
        function getFieldTypeSupportInfo(fieldType) {
            const supportMap = {
                'text': 'Fully supported',
                'textarea': 'Truncated to 50 characters',
                'number': 'Fully supported & sortable',
                'email': 'Fully supported',
                'url': 'Fully supported',
                'password': 'Displayed as masked text',
                'image': 'Displayed as thumbnail',
                'file': 'Displayed as link',
                'wysiwyg': 'Truncated to 50 characters',
                'select': 'Fully supported & sortable',
                'checkbox': 'Displayed as comma-separated list',
                'radio': 'Fully supported',
                'true_false': 'Displayed as checkmark',
                'link': 'Displayed as link',
                'post_object': 'Displayed as post title',
                'page_link': 'Displayed as link text',
                'relationship': 'Displayed as comma-separated titles',
                'taxonomy': 'Displayed as comma-separated terms',
                'user': 'Displayed as username',
                'google_map': 'Displayed as address text',
                'date_picker': 'Fully supported & sortable',
                'date_time_picker': 'Fully supported',
                'time_picker': 'Fully supported',
                'color_picker': 'Displayed as color code'
            };
            
            return supportMap[fieldType] || 'Limited support';
        }
    });
})(jQuery);
