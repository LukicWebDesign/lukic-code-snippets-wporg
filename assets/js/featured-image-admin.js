jQuery(document).ready(function ($) {
    'use strict';

    // Handle click on featured image in admin table
    $(document).on('click', '.lukic-featured-image-wrapper', function (e) {
        e.preventDefault();

        var $wrapper = $(this);
        var objectId = $wrapper.data('object-id');
        var objectType = $wrapper.data('object-type'); // 'post' or 'term'
        var action = objectType === 'post' ? 'lukic_update_featured_image' : 'lukic_update_taxonomy_image';
        var nonce = lukic_fi_admin.nonce;

        // Check if removing image
        if ($(e.target).hasClass('lukic-fi-remove') || $(e.target).closest('.lukic-fi-remove').length) {
            e.stopPropagation(); // Don't trigger the image upload modal

            if (confirm(lukic_fi_admin.i18n.confirm_remove)) {
                $wrapper.addClass('loading');

                $.ajax({
                    url: lukic_fi_admin.ajax_url,
                    type: 'POST',
                    data: {
                        action: action,
                        object_id: objectId,
                        attachment_id: -1, // Indicates removal
                        nonce: nonce
                    },
                    success: function (response) {
                        $wrapper.removeClass('loading');
                        if (response.success) {
                            $wrapper.find('img').attr('src', response.data.image_url)
                                .addClass('Lukic-no-image-placeholder')
                                .removeAttr('srcset'); // Remove srcset if present
                            $wrapper.find('.lukic-fi-remove').hide();
                        } else {
                            alert(response.data.message || lukic_fi_admin.i18n.error);
                        }
                    },
                    error: function () {
                        $wrapper.removeClass('loading');
                        alert(lukic_fi_admin.i18n.error);
                    }
                });
            }
            return;
        }

        // --- Open Media Modal ---
        var mediaUploader;

        // If the uploader object has already been created, reopen the dialog
        if (mediaUploader) {
            mediaUploader.open();
            return;
        }

        // Extend the wp.media object
        mediaUploader = wp.media.frames.file_frame = wp.media({
            title: lukic_fi_admin.i18n.choose_image,
            button: {
                text: lukic_fi_admin.i18n.set_image
            },
            multiple: false,
            library: {
                type: 'image'
            }
        });

        // When a file is selected, grab the URL and set it as the image source
        mediaUploader.on('select', function () {
            var attachment = mediaUploader.state().get('selection').first().toJSON();

            $wrapper.addClass('loading');

            $.ajax({
                url: lukic_fi_admin.ajax_url,
                type: 'POST',
                data: {
                    action: action,
                    object_id: objectId,
                    attachment_id: attachment.id,
                    nonce: nonce
                },
                success: function (response) {
                    $wrapper.removeClass('loading');
                    if (response.success) {
                        // Update image
                        $wrapper.find('img').attr('src', response.data.image_url)
                            .removeClass('Lukic-no-image-placeholder')
                            .removeAttr('srcset');

                        // Show remove button
                        $wrapper.find('.lukic-fi-remove').show();
                    } else {
                        alert(response.data.message || lukic_fi_admin.i18n.error);
                    }
                },
                error: function () {
                    $wrapper.removeClass('loading');
                    alert(lukic_fi_admin.i18n.error);
                }
            });
        });

        // Open the uploader dialog
        mediaUploader.open();
    });
});
