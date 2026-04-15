<?php
/**
 * Shortcode Handler — Promo Banner
 *
 * @package PromoBanner
 * @since   1.0.0
 *
 * Usage examples:
 *   [promo_banner id="5"]
 *   [promo_banner id="5" position="before_content"]
 *   [promo_banner group="sale" position="floating"]
 *   [promo_banner location="after_header"]  ← renders all banners with this location
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class PB_Shortcode
 *
 * Registers the [promo_banner] shortcode with support for
 * rendering by ID, location slug, or banner_group taxonomy.
 */
class PB_Shortcode {

    public static function init() {
        add_shortcode( 'promo_banner', [ __CLASS__, 'render' ] );
    }

    /**
     * Shortcode callback.
     *
     * Accepted attributes:
     *   - id       (int)    — Render a single specific banner by post ID.
     *   - position (string) — CSS position class: inline | before_content | after_content | floating.
     *   - location (string) — Render all banners assigned to this location slug.
     *   - group    (string) — Render all banners in this banner_group taxonomy term (slug).
     *
     * @param array $atts Shortcode attributes.
     * @return string Rendered HTML output.
     */
    public static function render( $atts ) {
        $atts = shortcode_atts( [
            'id'       => '',
            'position' => 'inline',   // inline | before_content | after_content | floating
            'location' => '',
            'group'    => '',
        ], $atts, 'promo_banner' );

        ob_start();

        // ── Render a single banner by ID ──────────────────────────────────────
        if ( ! empty( $atts['id'] ) ) {
            $banner_id = absint( $atts['id'] );
            if ( PB_Frontend::should_display( $banner_id ) ) {
                PB_Frontend::render_banner( $banner_id, $atts['position'] );
            }

        // ── Render all banners assigned to a specific location slug ───────────
        } elseif ( ! empty( $atts['location'] ) ) {
            $banners = PB_Frontend::get_active_banners();
            foreach ( $banners as $banner ) {
                $locations = (array) ( get_post_meta( $banner->ID, '_pb_locations', true ) ?: [] );
                if ( in_array( $atts['location'], $locations, true ) && PB_Frontend::should_display( $banner->ID ) ) {
                    PB_Frontend::render_banner( $banner->ID, $atts['position'] );
                }
            }

        // ── Render all banners in a banner_group taxonomy term ────────────────
        } elseif ( ! empty( $atts['group'] ) ) {
            $banners = PB_Frontend::get_active_banners( [
                'tax_query' => [ [
                    'taxonomy' => 'banner_group',
                    'field'    => 'slug',
                    'terms'    => sanitize_text_field( $atts['group'] ),
                ] ],
            ] );
            foreach ( $banners as $banner ) {
                if ( PB_Frontend::should_display( $banner->ID ) ) {
                    PB_Frontend::render_banner( $banner->ID, $atts['position'] );
                }
            }
        }

        return ob_get_clean();
    }
}
