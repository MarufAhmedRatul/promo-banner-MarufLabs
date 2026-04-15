/**
 * Promo Banner — Admin JavaScript
 *
 * Handles: WP Color Picker, media uploader, show/hide target logic,
 * and one-click shortcode copy.
 *
 * Strings are injected via wp_localize_script() as PBAdmin.{key}.
 *
 * @package PromoBanner
 * @since   1.0.0
 */
(function ($) {
    'use strict';

    $(document).ready(function () {

        // ── Color Picker ─────────────────────────────────────────────────────
        $('.pb-color-picker').wpColorPicker();

        // ── Image Upload (WP Media Library) ──────────────────────────────────
        var mediaUploader;

        $(document).on('click', '.pb-upload-image', function (e) {
            e.preventDefault();
            var $btn     = $(this);
            var $wrapper = $btn.closest('.pb-image-upload');

            // Re-open existing uploader instance if already initialised.
            if (mediaUploader) {
                mediaUploader.open();
                return;
            }

            // Initialise the WP media frame.
            mediaUploader = wp.media({
                title:    (typeof PBAdmin !== 'undefined') ? PBAdmin.mediaTitle  : 'Select Background Image',
                button:   { text: (typeof PBAdmin !== 'undefined') ? PBAdmin.mediaButton : 'Use This Image' },
                multiple: false,
                library:  { type: 'image' },
            });

            mediaUploader.on('select', function () {
                var attachment = mediaUploader.state().get('selection').first().toJSON();

                // Store the attachment ID.
                $wrapper.find('#pb_bg_image_id').val(attachment.id);

                // Show / update the image preview.
                var $preview = $wrapper.find('img');
                if ($preview.length) {
                    $preview.attr('src', attachment.url);
                } else {
                    $wrapper.prepend('<img src="' + attachment.url + '" style="max-height:60px;margin-bottom:5px;display:block;" alt="" />');
                }

                // Append the Remove button if it does not already exist.
                if (!$wrapper.find('.pb-remove-image').length) {
                    var removeLabel = (typeof PBAdmin !== 'undefined') ? PBAdmin.removeLabel : 'Remove';
                    $btn.after('<button type="button" class="button pb-remove-image">' + removeLabel + '</button>');
                }
            });

            mediaUploader.open();
        });

        // Handle image removal.
        $(document).on('click', '.pb-remove-image', function (e) {
            e.preventDefault();
            var $wrapper = $(this).closest('.pb-image-upload');
            $wrapper.find('#pb_bg_image_id').val('');
            $wrapper.find('img').remove();
            $(this).remove();
        });

        // ── Show All / Specific Targets Toggle ───────────────────────────────
        /**
         * Hides the specific-target selects when "Show on all pages" is checked,
         * and reveals them when unchecked.
         */
        function toggleTargets() {
            if ($('#pb_show_all').is(':checked')) {
                $('#pb_specific_targets').slideUp(150);
            } else {
                $('#pb_specific_targets').slideDown(150);
            }
        }

        toggleTargets(); // Set initial state on page load.
        $('#pb_show_all').on('change', toggleTargets);

        // ── One-Click Shortcode Copy ─────────────────────────────────────────
        var copyTitle = (typeof PBAdmin !== 'undefined') ? PBAdmin.copyTitle : 'Click to copy shortcode';
        var copied    = (typeof PBAdmin !== 'undefined') ? PBAdmin.copied    : 'Copied!';

        $('.pb-shortcode-copy').attr('title', copyTitle).on('click', function () {
            var $el = $(this);
            var text = $el.text().trim();

            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(function () {
                    $el.attr('title', copied);
                    setTimeout(function () { $el.attr('title', copyTitle); }, 2000);
                });
            } else {
                // Fallback for older browsers.
                var $temp = $('<textarea>').val(text).appendTo('body').select();
                document.execCommand('copy');
                $temp.remove();
                $el.attr('title', copied);
                setTimeout(function () { $el.attr('title', copyTitle); }, 2000);
            }
        });

    });

})(jQuery);
