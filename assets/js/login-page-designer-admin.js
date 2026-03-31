/* global wp, jQuery */
(function ($) {
    'use strict';

    $(document).ready(function () {

        var $form = $('#lpd-form');
        var $previewBg = $('#lpd-preview-bg');
        var $previewCard = $('#lpd-preview-card');
        var $previewLogo = $('#lpd-preview-logo');
        var $previewSubmit = $('#lpd-preview-submit');
        var $previewLinks = $('.lpd-preview-link');
        var $previewLabels = $('.lpd-preview-label');

        // ── Color Pickers ────────────────────────────────────────────────────────
        $('.Lukic-color-picker').wpColorPicker({
            change: function () { setTimeout(updatePreview, 50); },
            clear: function () { setTimeout(updatePreview, 50); },
        });

        // ── Media Uploader ───────────────────────────────────────────────────────
        // Create a fresh wp.media frame per click to avoid target confusion
        $(document).on('click', '.lpd-upload-btn', function () {
            var targetId = $(this).data('target');
            var previewId = $(this).data('preview');

            var frame = wp.media({
                title: 'Select Image',
                button: { text: 'Use this image' },
                multiple: false,
            });

            frame.on('select', function () {
                var url = frame.state().get('selection').first().toJSON().url;
                $('#' + targetId).val(url).trigger('change');
                setPreviewImage(previewId, url);
                $('.lpd-remove-btn[data-target="' + targetId + '"]').show();
                updatePreview();
            });

            frame.open();
        });

        $(document).on('click', '.lpd-remove-btn', function () {
            var targetId = $(this).data('target');
            var previewId = $(this).data('preview');
            $('#' + targetId).val('').trigger('change');
            clearPreviewImage(previewId);
            $(this).hide();
            updatePreview();
        });

        function setPreviewImage(previewId, url) {
            var $w = $('#' + previewId);
            $w.find('img').remove();
            $w.append($('<img>').attr('src', url));
        }

        function clearPreviewImage(previewId) {
            $('#' + previewId).find('img').remove();
        }

        // ── Background-type toggle ───────────────────────────────────────────────
        function syncBgType(value) {
            $('.lpd-bg-sub').hide();
            $('#lpd_bg_sub_' + value).show();
            $('#lpd_bg_type_toggle .lpd-type-label').removeClass('is-active');
            $('#lpd_bg_type_toggle input[value="' + value + '"]').closest('.lpd-type-label').addClass('is-active');
        }

        $form.on('change', 'input[name$="[bg_type]"]', function () {
            syncBgType($(this).val());
            updatePreview();
        });

        // Shadow toggle active state
        $form.on('change', 'input[name$="[card_shadow]"]', function () {
            $('.lpd-shadow-toggle .lpd-type-label').removeClass('is-active');
            $(this).closest('.lpd-type-label').addClass('is-active');
            updatePreview();
        });

        // ── Live preview ─────────────────────────────────────────────────────────
        function getVal(id) { return $('#' + id).val(); }

        var shadowMap = {
            none: 'none',
            soft: '0 4px 14px rgba(0,0,0,0.13)',
            strong: '0 8px 32px rgba(0,0,0,0.28)',
        };

        function updatePreview() {
            // Background
            var bgType = $form.find('input[name$="[bg_type]"]:checked').val() || 'color';
            if (bgType === 'color') {
                $previewBg.css({ background: getVal('lpd_bg_color') || '#f0f0f1' });
            } else if (bgType === 'image') {
                var img = getVal('lpd_bg_image');
                $previewBg.css({ background: img ? 'url(' + img + ') center/cover no-repeat' : '#f0f0f1' });
            } else {
                var from = getVal('lpd_bg_gradient_from') || '#667eea';
                var to = getVal('lpd_bg_gradient_to') || '#764ba2';
                var angle = $('#lpd_bg_gradient_angle').val() || 135;
                $previewBg.css({ background: 'linear-gradient(' + angle + 'deg, ' + from + ', ' + to + ')' });
            }

            // Card
            $previewCard.css({
                backgroundColor: getVal('lpd_card_bg_color') || '#ffffff',
                borderRadius: ($('#lpd_card_border_radius').val() || 4) + 'px',
                boxShadow: shadowMap[$form.find('input[name$="[card_shadow]"]:checked').val()] || shadowMap.soft,
            });

            // Logo
            var logoSrc = getVal('lpd_logo_url');
            var logoW = $('#lpd_logo_width').val() || 80;
            $previewLogo.empty();
            if (logoSrc) {
                $previewLogo.append(
                    $('<img>').attr('src', logoSrc).css({ maxWidth: logoW + 'px', maxHeight: '60px', objectFit: 'contain' })
                );
            } else {
                $previewLogo.append('<span class="lpd-preview-logo-placeholder">Logo</span>');
            }

            // Labels / links / button
            $previewLabels.css('color', getVal('lpd_label_color') || '#3c4858');
            $previewLinks.css('color', getVal('lpd_link_color') || '#2271b1');
            $previewSubmit.css({
                backgroundColor: getVal('lpd_btn_bg_color') || '#2271b1',
                color: getVal('lpd_btn_text_color') || '#ffffff',
                borderRadius: ($('#lpd_btn_border_radius').val() || 3) + 'px',
            });
        }

        // Any numeric/select change also triggers preview
        $form.on('change input', 'input[type="number"], select', updatePreview);

        // ── Init ─────────────────────────────────────────────────────────────────
        syncBgType($form.find('input[name$="[bg_type]"]:checked').val() || 'color');
        updatePreview();

    }); // end document.ready

})(jQuery);
