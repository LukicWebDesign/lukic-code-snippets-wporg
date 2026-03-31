jQuery(document).ready(function ($) {
    var frame;
    var $uploadBtn = $('#lukic-upload-avatar-button');
    var $removeBtn = $('#lukic-remove-avatar-button');
    var $avatarId = $('#lukic-user-avatar-id');
    var $avatarPreview = $('#lukic-user-avatar-preview');

    $uploadBtn.on('click', function (e) {
        e.preventDefault();

        // If the media frame already exists, reopen it.
        if (frame) {
            frame.open();
            return;
        }

        // Create a new media frame
        frame = wp.media({
            title: lukic_profile_image.title,
            button: {
                text: lukic_profile_image.button
            },
            multiple: false
        });

        // When an image is selected in the media frame...
        frame.on('select', function () {
            // Get media attachment details from the frame state
            var attachment = frame.state().get('selection').first().toJSON();

            // Send the attachment id to our hidden input
            $avatarId.val(attachment.id);

            // Send the attachment url to our preview image
            var imageUrl = attachment.sizes && attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url;
            $avatarPreview.attr('src', imageUrl).show();

            // Show the remove button
            $removeBtn.css('display', 'inline-block');
        });

        // Finally, open the modal on click
        frame.open();
    });

    $removeBtn.on('click', function (e) {
        e.preventDefault();
        $avatarId.val('');
        $avatarPreview.hide();
        $(this).hide();
    });
});
