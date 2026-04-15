<?php
/**
 * Frontend Display & Hook Management — Promo Banner
 *
 * @package PromoBanner
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class PB_Frontend
 *
 * Handles:
 *  - Enqueueing frontend CSS & JS (with smart loading support).
 *  - Registering WordPress action hooks for each active banner's locations.
 *  - Querying active (published, within schedule) banners.
 *  - Determining whether a banner should be shown on the current page.
 *  - Rendering the banner HTML via the template file.
 */
class PB_Frontend {

    /**
     * Maps admin-facing location slugs to WordPress action hooks and priorities.
     *
     * @var array
     */
    private static $hook_map = [
        'after_header'         => [ 'hook' => 'wp_body_open',                   'priority' => 10 ],
        'before_footer'        => [ 'hook' => 'wp_footer',                      'priority' => 1  ],
        'after_woo_notices'    => [ 'hook' => 'woocommerce_before_main_content', 'priority' => 15 ],
        'before_woo_content'   => [ 'hook' => 'woocommerce_before_main_content', 'priority' => 5  ],
        'after_woo_content'    => [ 'hook' => 'woocommerce_after_main_content',  'priority' => 10 ],
        'before_woo_sidebar'   => [ 'hook' => 'woocommerce_sidebar',             'priority' => 1  ],
        // before_title_section & after_title_section are injected via
        // lilac_breadcrumb_get_template_part filter — see register_hooks().
    ];

    public static function init() {
        add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ] );
        add_action( 'wp',                 [ __CLASS__, 'register_hooks'  ] );
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Frontend Assets
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Enqueues the plugin's frontend stylesheet and script.
     *
     * Respects the 'pb_load_assets' option:
     *   - 'always'  → load on every page (default, safe for dynamic use).
     *   - 'smart'   → load only when at least one active banner exists.
     *
     * @return void
     */
    public static function enqueue_assets() {
        // Smart loading — skip assets entirely if no active banners exist.
        $load_strategy = get_option( 'pb_load_assets', 'always' );
        if ( 'smart' === $load_strategy && empty( self::get_active_banners() ) ) {
            return;
        }

        // Respect mobile visibility setting.
        $mobile_enabled = get_option( 'pb_mobile_enabled', '1' );
        if ( '1' !== $mobile_enabled && wp_is_mobile() ) {
            return;
        }

        wp_enqueue_style(
            'pb-frontend',
            PB_PLUGIN_URL . 'assets/css/pb-frontend.css',
            [],
            PB_VERSION
        );
        wp_enqueue_script(
            'pb-frontend',
            PB_PLUGIN_URL . 'assets/js/pb-frontend.js',
            [ 'jquery' ],
            PB_VERSION,
            true
        );
        wp_localize_script( 'pb-frontend', 'PBConfig', [
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'pb_dismiss' ),
        ] );
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Hook Registration
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Attaches each active banner to its configured WordPress action hook(s).
     * Called on the 'wp' hook so all conditional tags (is_page, is_shop, etc.) are available.
     *
     * @return void
     */
    public static function register_hooks() {
        $banners = self::get_active_banners();
        if ( empty( $banners ) ) {
            return;
        }

        foreach ( $banners as $banner ) {
            if ( ! self::should_display( $banner->ID ) ) {
                continue;
            }

            $locations = get_post_meta( $banner->ID, '_pb_locations', true ) ?: [];

            foreach ( (array) $locations as $loc ) {
                if ( 'shortcode_only' === $loc ) {
                    continue;
                }

                // Lilac theme: inject around the main-title-section-wrapper via filter.
                if ( 'before_title_section' === $loc || 'after_title_section' === $loc ) {
                    add_filter(
                        'lilac_breadcrumb_get_template_part',
                        static function ( $html ) use ( $banner, $loc ) {
                            ob_start();
                            self::render_banner( $banner->ID );
                            $banner_html = ob_get_clean();
                            return 'before_title_section' === $loc
                                ? $banner_html . $html
                                : $html . $banner_html;
                        },
                        10
                    );
                    continue;
                }

                if ( isset( self::$hook_map[ $loc ] ) ) {
                    $map = self::$hook_map[ $loc ];
                    add_action(
                        $map['hook'],
                        static function () use ( $banner ) {
                            self::render_banner( $banner->ID );
                        },
                        $map['priority']
                    );
                }
            }
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Active Banner Query
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Returns all published banners that are currently within their scheduled date range,
     * ordered by priority (ascending — lower = higher priority).
     *
     * @param array $args Optional additional WP_Query arguments to merge.
     * @return WP_Post[]
     */
    public static function get_active_banners( $args = [] ) {
        $now = current_time( 'Y-m-d\TH:i' );

        $query_args = array_merge( [
            'post_type'      => PB_POST_TYPE,
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'no_found_rows'  => true,   // Performance: skip SQL_CALC_FOUND_ROWS.
            'meta_query'     => [
                'relation' => 'AND',
                [
                    'relation' => 'OR',
                    [ 'key' => '_pb_start_date', 'compare' => 'NOT EXISTS' ],
                    [ 'key' => '_pb_start_date', 'value'   => '',   'compare' => '='  ],
                    [ 'key' => '_pb_start_date', 'value'   => $now, 'compare' => '<=' ],
                ],
                [
                    'relation' => 'OR',
                    [ 'key' => '_pb_end_date', 'compare' => 'NOT EXISTS' ],
                    [ 'key' => '_pb_end_date', 'value'   => '',   'compare' => '='  ],
                    [ 'key' => '_pb_end_date', 'value'   => $now, 'compare' => '>=' ],
                ],
            ],
            'orderby'  => 'meta_value_num',
            'meta_key' => '_pb_priority',
            'order'    => 'ASC',
        ], $args );

        return get_posts( $query_args );
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Display Eligibility Check
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Determines whether a specific banner should be rendered on the current page.
     *
     * Checks, in order:
     *  1. Dismiss cookie — if the visitor already dismissed, return false.
     *  2. "Show on all pages" option.
     *  3. Targeted pages.
     *  4. Blog category targeting.
     *  5. WooCommerce product category targeting.
     *  6. Falls back to true when no targets are configured (display everywhere).
     *
     * @param int $banner_id Post ID of the banner.
     * @return bool
     */
    public static function should_display( $banner_id ) {
        // Dismissed cookie check.
        if ( isset( $_COOKIE[ 'pb_dismissed_' . $banner_id ] ) ) {
            return false;
        }

        // Global visibility — show on all pages.
        if ( get_post_meta( $banner_id, '_pb_show_all', true ) ) {
            return true;
        }

        // Specific page targeting — cast to int: meta stores strings, WP conditional tags expect int.
        $target_pages = array_map( 'intval', (array) ( get_post_meta( $banner_id, '_pb_pages', true ) ?: [] ) );
        if ( ! empty( $target_pages ) && is_page( $target_pages ) ) {
            return true;
        }

        // Blog category targeting — cast to int.
        $target_cats = array_map( 'intval', (array) ( get_post_meta( $banner_id, '_pb_categories', true ) ?: [] ) );
        if ( ! empty( $target_cats ) && ( is_category( $target_cats ) || ( is_single() && has_category( $target_cats ) ) ) ) {
            return true;
        }

        // WooCommerce product category targeting — cast to int.
        $target_woo = array_map( 'intval', (array) ( get_post_meta( $banner_id, '_pb_woo_cats', true ) ?: [] ) );
        if ( ! empty( $target_woo ) && class_exists( 'WooCommerce' ) ) {
            if ( is_product_category( $target_woo ) ) {
                return true;
            }
            if ( is_product() ) {
                $product_cats = wp_get_post_terms( get_the_ID(), 'product_cat', [ 'fields' => 'ids' ] );
                if ( array_intersect( $target_woo, $product_cats ) ) {
                    return true;
                }
            }
        }

        // No specific targets configured — display everywhere as a fallback.
        if ( empty( $target_pages ) && empty( $target_cats ) && empty( $target_woo ) ) {
            return true;
        }

        return false;
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Banner Renderer
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Renders a single banner by loading the banner template file.
     * All necessary variables are set before the include.
     *
     * @param int    $banner_id Post ID of the banner to render.
     * @param string $position  Optional shortcode position class suffix.
     * @return void
     */
    public static function render_banner( $banner_id, $position = '' ) {
        $post = get_post( $banner_id );
        if ( ! $post || 'publish' !== $post->post_status ) {
            return;
        }

        // Gather all meta values.
        $title       = get_the_title( $banner_id );
        $subtitle    = get_post_meta( $banner_id, '_pb_subtitle',    true );
        $link_text   = get_post_meta( $banner_id, '_pb_link_text',   true );
        $link_url    = get_post_meta( $banner_id, '_pb_link_url',    true );
        $link_target = get_post_meta( $banner_id, '_pb_link_target', true ) ?: '_self';
        $bg_color    = get_post_meta( $banner_id, '_pb_bg_color',    true ) ?: '#1a5276';
        $text_color  = get_post_meta( $banner_id, '_pb_text_color',  true ) ?: '#ffffff';
        $link_color  = get_post_meta( $banner_id, '_pb_link_color',  true ) ?: '#ffffff';
        $bg_image_id = get_post_meta( $banner_id, '_pb_bg_image_id', true );
        $animation   = get_post_meta( $banner_id, '_pb_animation',   true ) ?: 'slide_down';
        $dismissible = get_post_meta( $banner_id, '_pb_dismissible', true );
        $cookie_days = get_post_meta( $banner_id, '_pb_cookie_days', true ) ?: 1;
        $padding     = get_post_meta( $banner_id, '_pb_padding',     true ) ?: '20px 40px';
        $font_size   = get_post_meta( $banner_id, '_pb_font_size',   true ) ?: 18;

        // Build the inline background style.
        $bg_style = 'background-color:' . esc_attr( $bg_color ) . ';';
        if ( $bg_image_id ) {
            $bg_url = wp_get_attachment_image_url( $bg_image_id, 'full' );
            if ( $bg_url ) {
                $bg_style .= 'background-image:url(' . esc_url( $bg_url ) . ');background-size:cover;background-position:center;';
            }
        }

        $wrapper_classes = [
            'pb-banner',
            'pb-anim-' . sanitize_html_class( $animation ),
            $position ? 'pb-pos-' . sanitize_html_class( $position ) : '',
        ];

        include PB_PLUGIN_DIR . 'templates/banner-template.php';
    }
}

// ─── AJAX: Banner Dismiss ─────────────────────────────────────────────────────
add_action( 'wp_ajax_pb_dismiss',        'pb_handle_dismiss' );
add_action( 'wp_ajax_nopriv_pb_dismiss', 'pb_handle_dismiss' );

/**
 * AJAX handler for banner dismiss.
 * Sets a server-side cookie to suppress the banner for the configured duration.
 *
 * @return void
 */
function pb_handle_dismiss() {
    check_ajax_referer( 'pb_dismiss', 'nonce' );

    $banner_id   = absint( $_POST['banner_id'] );
    $cookie_days = absint( $_POST['cookie_days'] );

    if ( $banner_id ) {
        setcookie(
            'pb_dismissed_' . $banner_id,
            '1',
            time() + ( $cookie_days * DAY_IN_SECONDS ),
            COOKIEPATH,
            COOKIE_DOMAIN
        );
    }

    wp_send_json_success();
}
