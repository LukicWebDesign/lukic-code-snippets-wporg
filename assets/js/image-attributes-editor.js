/**
 * Lukic Code Snippets - Image Attributes Editor
 * JS functionality for the image attributes editor
 */
(function ($) {
    $(document).ready(function () {
        // Variables to track selected images
        var selectedImageIds = [];

        // Initialize DataTables
        var table = $("#Lukic-image-attributes-table").DataTable({
            "searching": true,
            "paging": true,
            "info": true,
            "responsive": true,
            "autoWidth": false,
            "language": {
                "search": "Search:",
                "searchPlaceholder": "Filter images..."
            },
            "columnDefs": [
                { "width": "5%", "targets": 0 },  // Checkbox column
                { "width": "15%", "targets": 1 }, // Thumbnail
                { "width": "18%", "targets": 2 }, // Title
                { "width": "18%", "targets": 3 }, // Alt
                { "width": "15%", "targets": 4 }, // File Name
                { "width": "8%", "targets": 5 }, // File Size
                { "width": "10%", "targets": 6 }, // Edit button
                { "width": "10%", "targets": 7 }  // Delete button
            ]
        });

        // Initialize Thickbox for image lightbox (replaces deprecated Magnific Popup)
        $('.thumbnail-link').on('click', function(e) {
            e.preventDefault();
            var imageUrl = $(this).attr('href');
            var imageTitle = $(this).find('img').attr('alt') || 'Image Preview';
            tb_show(imageTitle, imageUrl + '?TB_iframe=false&width=800&height=600');
        });

        // Handle Select All checkbox
        $("#select-all-images").on("change", function () {
            var isChecked = this.checked;
            $(".image-select-checkbox").prop("checked", isChecked);
            updateSelectedImages();
        });

        // Handle individual image selection
        $("#Lukic-image-attributes-table").on("change", ".image-select-checkbox", function () {
            updateSelectedImages();

            // If any checkbox is unchecked, uncheck the "select all" checkbox
            if (!this.checked) {
                $("#select-all-images").prop("checked", false);
            } else if ($(".image-select-checkbox:not(:checked)").length === 0) {
                // If all checkboxes are checked, check the "select all" checkbox
                $("#select-all-images").prop("checked", true);
            }
        });

        // Update selected images list and UI
        function updateSelectedImages() {
            selectedImageIds = [];
            $(".image-select-checkbox:checked").each(function () {
                selectedImageIds.push($(this).data("id"));
            });

            // Update count in bulk edit panel
            $("#selected-count").text("(" + selectedImageIds.length + ")");

            // Show or hide bulk edit panel based on selection
            if (selectedImageIds.length > 0) {
                $("#Lukic-bulk-edit-panel").show();
            } else {
                $("#Lukic-bulk-edit-panel").hide();
            }
        }

        // Clear selection
        $("#clear-selection").on("click", function () {
            $(".image-select-checkbox").prop("checked", false);
            $("#select-all-images").prop("checked", false);
            updateSelectedImages();
        });

        // Apply bulk edit
        $("#apply-bulk-edit").on("click", function () {
            if (selectedImageIds.length === 0) {
                alert("No images selected. Please select images to edit.");
                return;
            }

            const altText = $("#bulk-alt-text").val();
            const title = $("#bulk-title").val();
            const caption = $("#bulk-caption").val();

            if (!altText && !title && !caption) {
                alert("Please enter at least one field to update.");
                return;
            }

            // Show loading indicator
            $(this).addClass("Lukic-btn-loading").text("Updating...");

            // Make AJAX request to update fields
            $.ajax({
                url: LukicImageEditor.ajaxurl,
                method: "POST",
                data: {
                    action: "Lukic_bulk_update_images",
                    image_ids: selectedImageIds,
                    alt: altText,
                    title: title,
                    caption: caption,
                    nonce: LukicImageEditor.nonce
                },
                success: function (response) {
                    if (response.success) {
                        // Update table with new values
                        if (title) {
                            selectedImageIds.forEach(function (id) {
                                $('td[data-field="title"][data-id="' + id + '"]').text(title);
                            });
                        }

                        if (altText) {
                            selectedImageIds.forEach(function (id) {
                                $('td[data-field="alt"][data-id="' + id + '"]').text(altText);
                            });
                        }

                        if (caption) {
                            selectedImageIds.forEach(function (id) {
                                $('td[data-field="caption"][data-id="' + id + '"]').text(caption);
                            });
                        }

                        // Clear form fields
                        $("#bulk-alt-text, #bulk-title, #bulk-caption").val("");

                        // Show success message
                        alert(response.data.message);
                    } else {
                        alert("Error: " + response.data.message);
                    }
                },
                error: function (xhr, status, error) {
                    console.error(error);
                    alert("An error occurred while updating images");
                },
                complete: function () {
                    // Remove loading indicator
                    $("#apply-bulk-edit").removeClass("Lukic-btn-loading").text("Apply Changes");
                }
            });
        });

        // Handle cell editing
        $("#Lukic-image-attributes-table").on("blur", "td[contenteditable=true]", function () {
            var field = $(this).data("field");
            var id = $(this).data("id");
            var value = $(this).text();
            var $cell = $(this);

            // Add loading indicator
            $cell.addClass("Lukic-editing");

            // Make AJAX request to update field
            $.ajax({
                url: LukicImageEditor.ajaxurl,
                method: "POST",
                data: {
                    action: "Lukic_update_image_attribute",
                    field: field,
                    id: id,
                    value: value,
                    nonce: LukicImageEditor.nonce
                },
                success: function (response) {
                    if (response.success) {
                        // Show success indicator
                        $cell.removeClass("Lukic-editing");
                        $cell.addClass("Lukic-saved");

                        // Remove the class after 1 second
                        setTimeout(function () {
                            $cell.removeClass("Lukic-saved");
                        }, 1000);
                    } else {
                        // Show error indicator
                        $cell.removeClass("Lukic-editing");
                        $cell.addClass("Lukic-error");

                        // Display error message
                        console.error(response.data.message);

                        // Remove the class after 1 second
                        setTimeout(function () {
                            $cell.removeClass("Lukic-error");
                        }, 1000);
                    }
                },
                error: function (xhr, status, error) {
                    // Show error indicator
                    $cell.removeClass("Lukic-editing");
                    $cell.addClass("Lukic-error");

                    // Log error
                    console.error(error);

                    // Remove the class after 1 second
                    setTimeout(function () {
                        $cell.removeClass("Lukic-error");
                    }, 1000);
                }
            });
        });

        // Handle download CSV
        $("#download-csv").on("click", function (e) {
            e.preventDefault();
            window.location.href = LukicImageEditor.ajaxurl + "?action=Lukic_generate_csv_file&nonce=" + LukicImageEditor.nonce;
        });

        // Handle delete button click
        $("#Lukic-image-attributes-table").on("click", ".delete-image-btn", function () {
            if (!confirm("Are you sure you want to delete this image? This action cannot be undone.")) {
                return;
            }

            var id = $(this).data("id");
            var $row = $(this).closest("tr");

            // Add loading indicator
            $row.addClass("Lukic-deleting");

            $.ajax({
                url: LukicImageEditor.ajaxurl,
                method: "POST",
                data: {
                    action: "Lukic_delete_image",
                    id: id,
                    nonce: LukicImageEditor.nonce
                },
                success: function (response) {
                    if (response.success) {
                        // Remove the row and redraw the table
                        table.row($row).remove().draw();
                    } else {
                        // Show error
                        $row.removeClass("Lukic-deleting");
                        alert(response.data.message || "Failed to delete image");
                    }
                },
                error: function (xhr, status, error) {
                    // Show error
                    $row.removeClass("Lukic-deleting");
                    console.error(error);
                    alert("An error occurred while deleting the image");
                }
            });
        });

        // Handle edit button click
        $("#Lukic-image-attributes-table").on("click", ".edit-image-btn", function () {
            var id = $(this).data("id");
            // Navigate to WordPress media edit screen
            window.location.href = "post.php?post=" + id + "&action=edit";
        });

        // Reattach event listeners after pagination change
        table.on("draw.dt", function () {
            // Re-bind Thickbox click handler for new rows
            $('.thumbnail-link').off('click').on('click', function(e) {
                e.preventDefault();
                var imageUrl = $(this).attr('href');
                var imageTitle = $(this).find('img').attr('alt') || 'Image Preview';
                tb_show(imageTitle, imageUrl + '?TB_iframe=false&width=800&height=600');
            });

            // Update selected status after table redraw
            updateSelectedImages();
        });
    });
})(jQuery);
