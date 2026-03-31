/**
 * Lukic Snippet Codes - Content Order
 * JavaScript for the content ordering functionality
 */

(function($) {
    'use strict';
    
    // Function to log debug messages only in development environments
    function debug(message) {
        // Uncomment for debugging if needed
        // if (window.console && console.log) {
        //     console.log('Lukic Content Order: ' + message);
        // }
    }
    
    $(document).ready(function() {
        debug('Document ready, initializing content order functionality');
        
        const $orderList = $('.Lukic-content-order-list');
        const $statusMessage = $('.Lukic-content-order-status');
        
        if ($orderList.length) {
            debug('Found order list with ' + $orderList.find('.Lukic-content-order-item').length + ' items');
            const postType = $orderList.data('post-type');
            
            if (!$.fn.sortable) {
                debug('ERROR: jQuery UI Sortable not loaded!');
                showStatus('Error: Required jQuery UI components are not loaded. Please contact administrator.', 'error');
                return;
            }
            
            try {
                // Initialize sortable with enhanced options
                $orderList.sortable({
                    // Remove the handle option to make the entire item draggable
                    // handle: '.Lukic-content-order-handle',
                    placeholder: 'Lukic-content-order-placeholder',
                    opacity: 0.8,
                    cursor: 'grab',
                    axis: 'y',
                    scrollSensitivity: 40,
                    forcePlaceholderSize: true,
                    helper: function(e, ui) {
                        // Create a clone with fixed width to prevent shrinking during drag
                        const $clone = $(ui).clone();
                        $clone.css('width', $(ui).width() + 'px');
                        return $clone;
                    },
                    start: function(event, ui) {
                        debug('Drag started');
                        // Change cursor to grabbing during drag
                        $('body').css('cursor', 'grabbing');
                        // Add visual cue for the item being moved
                        ui.helper.addClass('ui-sortable-helper');
                        
                        // Adjust placeholder height to match item
                        ui.placeholder.height(ui.item.outerHeight());
                    },
                    stop: function(event, ui) {
                        debug('Drag stopped');
                        // Reset cursor
                        $('body').css('cursor', '');
                        // Use alternative highlighting method that doesn't require jQuery UI Effects
                        if (ui.item && ui.item.length) {
                            const $item = $(ui.item);
                            $item.css('background-color', '#f0f8ff');
                            setTimeout(function() {
                                $item.css('transition', 'background-color 0.5s');
                                $item.css('background-color', '');
                                // Reset the transition after the animation completes
                                setTimeout(function() {
                                    $item.css('transition', '');
                                }, 500);
                            }, 0);
                        }
                    },
                    update: function(event, ui) {
                        debug('Order changed, updating...');
                        updateOrder();
                    }
                }).disableSelection();
                
                debug('Sortable initialized successfully');
                
                // Make the handle more prominent on hover
                $orderList.on('mouseenter', '.Lukic-content-order-item', function() {
                    $(this).find('.Lukic-content-order-handle').addClass('active');
                }).on('mouseleave', '.Lukic-content-order-item', function() {
                    $(this).find('.Lukic-content-order-handle').removeClass('active');
                });
                
                // Add quick action buttons
                $orderList.find('.Lukic-content-order-item').each(function() {
                    const $item = $(this);
                    const postId = $item.data('id');
                    const postTitle = $item.find('.Lukic-content-order-title').text();
                    
                    // Create edit link
                    const editUrl = `post.php?post=${postId}&action=edit`;
                    const viewUrl = $item.data('permalink') || '#';
                    
                    // Add actions
                    const $actions = $('<div class="row-actions"></div>');
                    $actions.append(`<span class="edit"><a href="${editUrl}">Edit</a></span>`);
                    
                    if (viewUrl !== '#') {
                        $actions.append(`<span class="view"><a href="${viewUrl}" target="_blank">View</a></span>`);
                    }
                    
                    $item.append($actions);
                    
                    // Add drag tip to the right side of the item
                    $item.append('<span class="drag-tip">Drag to reorder</span>');
                });
                
                // Add a visual indicator for draggable items
                // REMOVED: $orderList.find('.Lukic-content-order-handle').append('<span class="drag-tip">Drag to reorder</span>');
                
            } catch(e) {
                debug('Error initializing sortable: ' + e.message);
                showStatus('Error initializing drag and drop: ' + e.message, 'error');
            }
            
            // Update order function
            function updateOrder() {
                // Show loading message
                showStatus(LukicContentOrder.loading, 'loading');
                
                // Get the post IDs in their new order
                const postIds = [];
                $orderList.find('.Lukic-content-order-item').each(function() {
                    postIds.push($(this).data('id'));
                });
                
                debug('Updating order with IDs: ' + postIds.join(', '));
                
                // Send AJAX request
                $.ajax({
                    url: LukicContentOrder.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'Lukic_update_post_order',
                        nonce: LukicContentOrder.nonce,
                        post_type: postType,
                        post_ids: postIds
                    },
                    success: function(response) {
                        debug('AJAX response received');
                        try {
                            if (response.success) {
                                debug('Order update successful');
                                showStatus(response.data.message, 'success');
                                
                                // Highlight the new order
                                $orderList.find('.Lukic-content-order-item').each(function(index) {
                                    const $item = $(this);
                                    setTimeout(function() {
                                        // Use CSS transitions instead of jQuery animate with color
                                        $item.css({
                                            'background-color': '#f0f8ff',
                                            'transition': 'background-color 0.5s'
                                        });
                                        
                                        setTimeout(function() {
                                            $item.css('background-color', '');
                                            // Reset transition after animation completes
                                            setTimeout(function() {
                                                $item.css('transition', '');
                                            }, 500);
                                        }, 300);
                                    }, index * 100);
                                });
                                
                                // Update display numbering if present
                                updateDisplayNumbers();
                            } else {
                                debug('Order update failed: ' + (response.data ? response.data.message : 'Unknown error'));
                                showStatus(response.data ? response.data.message : LukicContentOrder.error, 'error');
                            }
                        } catch(e) {
                            debug('Error processing AJAX response: ' + e.message);
                            showStatus('Error processing server response', 'error');
                        }
                    },
                    error: function(xhr, status, error) {
                        debug('AJAX error: ' + status + ' - ' + error);
                        showStatus(LukicContentOrder.error, 'error');
                    }
                });
            }
            
            // Update the display order numbers if present
            function updateDisplayNumbers() {
                if ($('.Lukic-content-order-number').length) {
                    $('.Lukic-content-order-item').each(function(index) {
                        $(this).find('.Lukic-content-order-number').text(index + 1);
                    });
                }
            }
            
            // Helper function to show status messages
            function showStatus(message, type) {
                debug('Status message: [' + type + '] ' + message);
                $statusMessage.removeClass('loading success error')
                    .addClass(type)
                    .html(message)
                    .fadeIn();
                
                if (type !== 'loading') {
                    setTimeout(function() {
                        $statusMessage.fadeOut();
                    }, 3000);
                }
            }
        } else {
            debug('Order list not found');
        }
    });
})(jQuery);
