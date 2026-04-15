/**
 * Promo Banner — Frontend JavaScript
 *
 * Handles: entrance animations, dismiss/close button, floating banner
 * body-padding compensation, and AJAX cookie setting.
 *
 * @package PromoBanner
 * @since   1.0.0
 */
(function ($) {
    'use strict';

    var PB = {

        /**
         * Entry point — called on DOMReady.
         */
        init: function () {
            this.animateBanners();
            this.bindDismiss();
            this.handleFloating();
        },

        // ── Entrance Animations ───────────────────────────────────────────────
        /**
         * Adds the `pb-animated` class to each banner after a short delay
         * so CSS keyframe animations are triggered after the page has painted.
         */
        animateBanners: function () {
            $('.pb-banner').each(function () {
                var $banner   = $(this);
                var animation = $banner.data('animation');

                if (animation === 'none') {
                    return; // Skip animation for this banner.
                }

                if (animation === 'slide_down' || animation === 'slide_up') {
                    $banner.css({ overflow: 'hidden' });
                }

                // Short delay ensures the animation plays after initial paint.
                setTimeout(function () {
                    $banner.addClass('pb-animated');
                }, 120);
            });
        },

        // ── Dismiss Button ────────────────────────────────────────────────────
        /**
         * Handles clicks on the close (✕) button.
         *
         * Two-phase dismiss:
         *  Phase 1 (CSS keyframe, ~380ms) — slides/fades the banner content out.
         *  Phase 2 (jQuery animate, 280ms) — smoothly collapses the height/padding
         *           so surrounding content slides up rather than jumping.
         *
         * A `removed` guard prevents the animationend listener and the
         * setTimeout fallback from both calling remove().
         */
        bindDismiss: function () {
            $(document).on('click', '.pb-close-btn', function () {
                var $banner    = $(this).closest('.pb-banner');
                var bannerId   = $banner.data('banner-id');
                var cookieDays = $banner.data('cookie-days') || 1;
                var removed    = false;

                // Determine phase-1 animation duration from banner type.
                var isFadeOrNone = $banner.hasClass('pb-anim-fade_in') || $banner.hasClass('pb-anim-none');
                var phase1Ms = isFadeOrNone ? 280 : 380;

                // ── Phase 1: trigger CSS exit animation ───────────────────────
                $banner.addClass('pb-dismissing');

                // ── Phase 2: height collapse after phase 1 finishes ───────────
                function collapseAndRemove() {
                    if (removed) { return; }

                    // Snapshot the current computed height so the animation
                    // starts from the real value (not 'auto').
                    var currentHeight  = $banner.outerHeight(true);
                    var currentPadding = parseInt( $banner.css('padding-top'), 10 )
                                      + parseInt( $banner.css('padding-bottom'), 10 );
                    var currentMargin  = parseInt( $banner.css('margin-top'), 10 )
                                      + parseInt( $banner.css('margin-bottom'), 10 );

                    $banner.css({
                        overflow:      'hidden',
                        height:        currentHeight,
                        'padding-top':    parseInt( $banner.css('padding-top'),    10 ),
                        'padding-bottom': parseInt( $banner.css('padding-bottom'), 10 ),
                        'margin-top':     parseInt( $banner.css('margin-top'),     10 ),
                        'margin-bottom':  parseInt( $banner.css('margin-bottom'),  10 ),
                    });

                    $banner.animate({
                        height:          0,
                        'padding-top':   0,
                        'padding-bottom':0,
                        'margin-top':    0,
                        'margin-bottom': 0,
                    }, {
                        duration: 280,
                        easing:   'swing',
                        complete: function () {
                            if (removed) { return; }
                            removed = true;
                            $banner.remove();
                            PB.updateFloatingPadding();
                        },
                    });
                }

                // Primary trigger: wait for CSS animation to finish.
                $banner.one('animationend webkitAnimationEnd', function () {
                    collapseAndRemove();
                });

                // Fallback: if animationend never fires (e.g. prefers-reduced-motion
                // or browser quirk), collapse after phase-1 duration.
                setTimeout(function () {
                    collapseAndRemove();
                }, phase1Ms + 20);

                // Notify the server — set dismiss cookie via AJAX.
                if (typeof PBConfig !== 'undefined') {
                    $.post(PBConfig.ajaxurl, {
                        action:      'pb_dismiss',
                        nonce:       PBConfig.nonce,
                        banner_id:   bannerId,
                        cookie_days: cookieDays,
                    });
                }
            });
        },


        // ── Floating Banner Padding ───────────────────────────────────────────
        /**
         * For fixed-position floating banners: adds padding-top to <body>
         * equal to the total height of all floating banners so page content
         * is not obscured. Also compensates for the WP Admin Bar height.
         */
        handleFloating: function () {
            var $floating = $('.pb-pos-floating');
            if (!$floating.length) {
                return;
            }

            $('body').addClass('has-pb-floating');
            this.updateFloatingPadding();

            // Recalculate on window resize to handle orientation changes.
            var self = this;
            $(window).on('resize', function () {
                self.updateFloatingPadding();
            });
        },

        /**
         * Recalculates and applies body padding-top for floating banners.
         * Called after banner removal to close the gap left by a dismissed banner.
         */
        updateFloatingPadding: function () {
            var $floating   = $('.pb-pos-floating:not(.pb-dismissing)');
            var totalHeight = 0;

            $floating.each(function () {
                totalHeight += $(this).outerHeight(true);
            });

            // Account for the WP Admin Bar (present when logged in).
            var adminBarH = $('#wpadminbar').length ? $('#wpadminbar').outerHeight() : 0;
            $floating.css('top', adminBarH + 'px');

            $('body').css('padding-top', totalHeight + adminBarH + 'px');
        },

    };

    // ── DOM Ready ────────────────────────────────────────────────────────────
    $(document).ready(function () {
        PB.init();
    });

})(jQuery);
