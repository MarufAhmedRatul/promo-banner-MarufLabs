<?php
/**
 * Admin Meta Boxes & Settings — Promo Banner
 *
 * @package PromoBanner
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class PB_Admin
 *
 * Registers and renders all admin meta boxes for the promo_banner CPT.
 * Also handles saving meta, custom list table columns, and admin asset loading.
 */
class PB_Admin {

    public static function init() {
        add_action( 'add_meta_boxes',                                   [ __CLASS__, 'add_meta_boxes'    ] );
        add_action( 'save_post_' . PB_POST_TYPE,                        [ __CLASS__, 'save_meta'         ] );
        add_action( 'admin_enqueue_scripts',                            [ __CLASS__, 'enqueue_scripts'   ] );
        add_filter( 'manage_' . PB_POST_TYPE . '_posts_columns',        [ __CLASS__, 'custom_columns'    ] );
        add_action( 'manage_' . PB_POST_TYPE . '_posts_custom_column',  [ __CLASS__, 'render_columns'    ], 10, 2 );
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Admin Assets
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Enqueues admin scripts and styles only on promo_banner screens.
     *
     * @param string $hook Current admin page hook.
     * @return void
     */
    public static function enqueue_scripts( $hook ) {
        $screen = get_current_screen();
        if ( ! $screen || $screen->post_type !== PB_POST_TYPE ) {
            return;
        }

        wp_enqueue_style( 'wp-color-picker' );
        wp_enqueue_media();
        wp_enqueue_script( 'wp-color-picker' );

        wp_enqueue_script(
            'pb-admin',
            PB_PLUGIN_URL . 'assets/js/pb-admin.js',
            [ 'jquery', 'wp-color-picker' ],
            PB_VERSION,
            true
        );

        // Pass translatable strings and config to the admin JS.
        wp_localize_script( 'pb-admin', 'PBAdmin', [
            'mediaTitle'  => __( 'Select Background Image', 'promo-banner' ),
            'mediaButton' => __( 'Use This Image', 'promo-banner' ),
            'removeLabel' => __( 'Remove', 'promo-banner' ),
            'copyTitle'   => __( 'Click to copy shortcode', 'promo-banner' ),
            'copied'      => __( 'Copied!', 'promo-banner' ),
        ] );
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Meta Boxes
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Registers all meta boxes for the promo_banner edit screen.
     *
     * @return void
     */
    public static function add_meta_boxes() {
        add_meta_box( 'pb_content',   __( '📝 Banner Content',       'promo-banner' ), [ __CLASS__, 'render_content_box'   ], PB_POST_TYPE, 'normal', 'high'    );
        add_meta_box( 'pb_design',    __( '🎨 Design Settings',       'promo-banner' ), [ __CLASS__, 'render_design_box'    ], PB_POST_TYPE, 'normal', 'high'    );
        add_meta_box( 'pb_display',   __( '📍 Display Location',      'promo-banner' ), [ __CLASS__, 'render_display_box'   ], PB_POST_TYPE, 'side',   'high'    );
        add_meta_box( 'pb_schedule',  __( '📅 Schedule & Status',     'promo-banner' ), [ __CLASS__, 'render_schedule_box'  ], PB_POST_TYPE, 'side',   'default' );
        add_meta_box( 'pb_shortcode', __( '🔗 Shortcode',             'promo-banner' ), [ __CLASS__, 'render_shortcode_box' ], PB_POST_TYPE, 'side',   'default' );
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Content Meta Box
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Renders the Banner Content meta box (title, subtitle, link fields).
     *
     * @param WP_Post $post Current post object.
     * @return void
     */
    public static function render_content_box( $post ) {
        wp_nonce_field( 'pb_save_meta', 'pb_nonce' );
        $subtitle    = get_post_meta( $post->ID, '_pb_subtitle',    true );
        $link_text   = get_post_meta( $post->ID, '_pb_link_text',   true );
        $link_url    = get_post_meta( $post->ID, '_pb_link_url',    true );
        $link_target = get_post_meta( $post->ID, '_pb_link_target', true );
        ?>
        <style>
            .pb-meta-table { width:100%; border-collapse:collapse; }
            .pb-meta-table th { text-align:left; padding:8px 10px 4px; font-weight:600; color:#1d2327; width:160px; }
            .pb-meta-table td { padding:4px 10px 10px; }
            .pb-meta-table input[type=text],
            .pb-meta-table textarea { width:100%; }
            .pb-meta-table textarea { height:60px; resize:vertical; }
            .pb-section-title { background:#f0f6ff; padding:6px 10px; font-weight:700; color:#2271b1; border-left:3px solid #2271b1; margin:10px 0 6px; font-size:12px; }
        </style>
        <table class="pb-meta-table">
            <tr>
                <th><?php esc_html_e( 'Title', 'promo-banner' ); ?></th>
                <td><input type="text" name="post_title" value="<?php echo esc_attr( $post->post_title ); ?>" style="width:100%;" /></td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'Subtitle', 'promo-banner' ); ?></th>
                <td><textarea name="pb_subtitle"><?php echo esc_textarea( $subtitle ); ?></textarea></td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'Button Text', 'promo-banner' ); ?></th>
                <td><input type="text" name="pb_link_text" value="<?php echo esc_attr( $link_text ); ?>" placeholder="<?php esc_attr_e( 'e.g. Shop Now', 'promo-banner' ); ?>" /></td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'Button URL', 'promo-banner' ); ?></th>
                <td><input type="text" name="pb_link_url" value="<?php echo esc_attr( $link_url ); ?>" placeholder="https://" /></td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'Link Target', 'promo-banner' ); ?></th>
                <td>
                    <select name="pb_link_target">
                        <option value="_self"  <?php selected( $link_target, '_self'  ); ?>><?php esc_html_e( 'Same tab (_self)',  'promo-banner' ); ?></option>
                        <option value="_blank" <?php selected( $link_target, '_blank' ); ?>><?php esc_html_e( 'New tab (_blank)', 'promo-banner' ); ?></option>
                    </select>
                </td>
            </tr>
        </table>
        <?php
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Design Meta Box
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Renders the Design Settings meta box (colors, image, animation, etc.).
     *
     * @param WP_Post $post Current post object.
     * @return void
     */
    public static function render_design_box( $post ) {
        $bg_color     = get_post_meta( $post->ID, '_pb_bg_color',    true ) ?: '#1a5276';
        $text_color   = get_post_meta( $post->ID, '_pb_text_color',  true ) ?: '#ffffff';
        $link_color   = get_post_meta( $post->ID, '_pb_link_color',  true ) ?: '#ffffff';
        $bg_image_id  = get_post_meta( $post->ID, '_pb_bg_image_id', true );
        $bg_image_url = $bg_image_id ? wp_get_attachment_image_url( $bg_image_id, 'full' ) : '';
        $animation    = get_post_meta( $post->ID, '_pb_animation',   true ) ?: get_option( 'pb_default_animation', 'slide_down' );
        $dismissible  = get_post_meta( $post->ID, '_pb_dismissible', true );
        $cookie_days  = get_post_meta( $post->ID, '_pb_cookie_days', true ) ?: get_option( 'pb_default_cookie_days', 1 );
        $padding      = get_post_meta( $post->ID, '_pb_padding',     true ) ?: '20px 40px';
        $font_size    = get_post_meta( $post->ID, '_pb_font_size',   true ) ?: '18';
        ?>
        <table class="pb-meta-table">
            <tr>
                <th><?php esc_html_e( 'Background Color', 'promo-banner' ); ?></th>
                <td><input type="text" name="pb_bg_color" value="<?php echo esc_attr( $bg_color ); ?>" class="pb-color-picker" /></td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'Text Color', 'promo-banner' ); ?></th>
                <td><input type="text" name="pb_text_color" value="<?php echo esc_attr( $text_color ); ?>" class="pb-color-picker" /></td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'Link / Button Color', 'promo-banner' ); ?></th>
                <td><input type="text" name="pb_link_color" value="<?php echo esc_attr( $link_color ); ?>" class="pb-color-picker" /></td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'Background Image', 'promo-banner' ); ?></th>
                <td>
                    <div class="pb-image-upload">
                        <?php if ( $bg_image_url ) : ?>
                            <img src="<?php echo esc_url( $bg_image_url ); ?>" style="max-height:60px;margin-bottom:5px;display:block;" alt="" />
                        <?php endif; ?>
                        <input type="hidden" name="pb_bg_image_id" id="pb_bg_image_id" value="<?php echo esc_attr( $bg_image_id ); ?>" />
                        <button type="button" class="button pb-upload-image"><?php esc_html_e( 'Select Image', 'promo-banner' ); ?></button>
                        <?php if ( $bg_image_id ) : ?>
                            <button type="button" class="button pb-remove-image"><?php esc_html_e( 'Remove', 'promo-banner' ); ?></button>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'Animation', 'promo-banner' ); ?></th>
                <td>
                    <select name="pb_animation">
                        <option value="slide_down" <?php selected( $animation, 'slide_down' ); ?>><?php esc_html_e( 'Slide Down', 'promo-banner' ); ?></option>
                        <option value="slide_up"   <?php selected( $animation, 'slide_up'   ); ?>><?php esc_html_e( 'Slide Up',   'promo-banner' ); ?></option>
                        <option value="fade_in"    <?php selected( $animation, 'fade_in'    ); ?>><?php esc_html_e( 'Fade In',    'promo-banner' ); ?></option>
                        <option value="none"       <?php selected( $animation, 'none'       ); ?>><?php esc_html_e( 'None',       'promo-banner' ); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'Title Font Size (px)', 'promo-banner' ); ?></th>
                <td><input type="number" name="pb_font_size" value="<?php echo esc_attr( $font_size ); ?>" min="10" max="60" style="width:80px;" /> px</td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'Padding', 'promo-banner' ); ?></th>
                <td><input type="text" name="pb_padding" value="<?php echo esc_attr( $padding ); ?>" placeholder="20px 40px" style="width:150px;" /></td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'Close Button', 'promo-banner' ); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="pb_dismissible" value="1" <?php checked( $dismissible, 1 ); ?> />
                        <?php esc_html_e( 'Show a dismiss (✕) button', 'promo-banner' ); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'Cookie Duration (days)', 'promo-banner' ); ?></th>
                <td>
                    <input type="number" name="pb_cookie_days" value="<?php echo esc_attr( $cookie_days ); ?>" min="0" max="365" style="width:80px;" />
                    <span class="description"><?php esc_html_e( 'How long to hide the banner after dismissal. 0 = session only.', 'promo-banner' ); ?></span>
                </td>
            </tr>
        </table>
        <?php
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Display Location Meta Box
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Renders the Display Location meta box (hook selectors + page/category targeting).
     *
     * @param WP_Post $post Current post object.
     * @return void
     */
    public static function render_display_box( $post ) {
        $locations       = (array) ( get_post_meta( $post->ID, '_pb_locations',  true ) ?: [] );
        // Cast to int — meta is stored as strings; page/term IDs are ints. strict in_array() would fail without this.
        $target_pages    = array_map( 'intval', (array) ( get_post_meta( $post->ID, '_pb_pages',      true ) ?: [] ) );
        $target_cats     = array_map( 'intval', (array) ( get_post_meta( $post->ID, '_pb_categories', true ) ?: [] ) );
        $target_woo_cats = array_map( 'intval', (array) ( get_post_meta( $post->ID, '_pb_woo_cats',   true ) ?: [] ) );
        $show_all        = get_post_meta( $post->ID, '_pb_show_all',   true );

        $available_locations = [
            'after_header'        => __( '🔝 After Header',                        'promo-banner' ),
            'before_footer'       => __( '🔚 Before Footer',                       'promo-banner' ),
            'after_woo_notices'   => __( '🛒 After WooCommerce Notices',           'promo-banner' ),
            'before_woo_content'  => __( '📦 Before WooCommerce Content',          'promo-banner' ),
            'before_title_section'=> __( '🍞 Before Breadcrumb / Title Section',   'promo-banner' ),
            'after_title_section' => __( '🍞 After Breadcrumb / Title Section',    'promo-banner' ),
            'after_woo_content'   => __( '📦 After WooCommerce Content',           'promo-banner' ),
            'before_woo_sidebar'  => __( '📌 Before WooCommerce Sidebar',          'promo-banner' ),
            'shortcode_only'      => __( '🔗 Shortcode Only (manual placement)',   'promo-banner' ),
        ];
        ?>
        <style>
            .pb-location-list label { display:block; margin:4px 0; font-size:13px; }
            .pb-target-section { margin-top:12px; }
            .pb-target-section h4 { font-size:12px; font-weight:700; color:#2271b1; margin:8px 0 4px; border-bottom:1px solid #e0e0e0; padding-bottom:3px; }
            .pb-target-section select { width:100%; min-height:80px; }
        </style>

        <div class="pb-location-list">
            <p><strong><?php esc_html_e( 'Hook Locations:', 'promo-banner' ); ?></strong></p>
            <?php foreach ( $available_locations as $key => $label ) : ?>
                <label>
                    <input type="checkbox" name="pb_locations[]" value="<?php echo esc_attr( $key ); ?>"
                        <?php checked( in_array( $key, (array) $locations, true ) ); ?> />
                    <?php echo esc_html( $label ); ?>
                </label>
            <?php endforeach; ?>
        </div>

        <div class="pb-target-section">
            <h4><?php esc_html_e( 'Visibility Scope', 'promo-banner' ); ?></h4>
            <label>
                <input type="checkbox" name="pb_show_all" value="1" <?php checked( $show_all, 1 ); ?> id="pb_show_all" />
                <?php esc_html_e( 'Show on all pages', 'promo-banner' ); ?>
            </label>
        </div>

        <div class="pb-target-section" id="pb_specific_targets">
            <h4><?php esc_html_e( 'Specific Pages:', 'promo-banner' ); ?></h4>
            <select name="pb_pages[]" multiple size="4">
                <?php
                foreach ( get_pages() as $page ) {
                    $selected = in_array( $page->ID, (array) $target_pages, true ) ? 'selected' : '';
                    echo '<option value="' . esc_attr( $page->ID ) . '" ' . $selected . '>' . esc_html( $page->post_title ) . '</option>';
                }
                ?>
            </select>

            <h4><?php esc_html_e( 'Blog Categories:', 'promo-banner' ); ?></h4>
            <select name="pb_categories[]" multiple size="4">
                <?php
                foreach ( get_categories( [ 'hide_empty' => false ] ) as $cat ) {
                    $selected = in_array( $cat->term_id, (array) $target_cats, true ) ? 'selected' : '';
                    echo '<option value="' . esc_attr( $cat->term_id ) . '" ' . $selected . '>' . esc_html( $cat->name ) . '</option>';
                }
                ?>
            </select>

            <?php if ( class_exists( 'WooCommerce' ) ) : ?>
            <h4><?php esc_html_e( 'WooCommerce Product Categories:', 'promo-banner' ); ?></h4>
            <select name="pb_woo_cats[]" multiple size="4">
                <?php
                $woo_cats = get_terms( [ 'taxonomy' => 'product_cat', 'hide_empty' => false ] );
                if ( ! is_wp_error( $woo_cats ) ) {
                    foreach ( $woo_cats as $wcat ) {
                        $selected = in_array( $wcat->term_id, (array) $target_woo_cats, true ) ? 'selected' : '';
                        echo '<option value="' . esc_attr( $wcat->term_id ) . '" ' . $selected . '>' . esc_html( $wcat->name ) . '</option>';
                    }
                }
                ?>
            </select>
            <?php endif; ?>

            <p class="description" style="margin-top:6px;font-size:11px;">
                <?php esc_html_e( 'Hold Ctrl / Cmd and click to select multiple items.', 'promo-banner' ); ?>
            </p>
        </div>
        <?php
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Schedule Meta Box
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Renders the Schedule & Status meta box (date range + priority).
     *
     * @param WP_Post $post Current post object.
     * @return void
     */
    public static function render_schedule_box( $post ) {
        $start_date = get_post_meta( $post->ID, '_pb_start_date', true );
        $end_date   = get_post_meta( $post->ID, '_pb_end_date',   true );
        $priority   = get_post_meta( $post->ID, '_pb_priority',   true ) ?: 10;
        ?>
        <style>
            .pb-schedule-field { margin-bottom: 12px; }
            .pb-schedule-field label {
                display: block;
                font-weight: 600;
                font-size: 12px;
                color: #1d2327;
                margin-bottom: 4px;
            }
            .pb-schedule-field input[type="datetime-local"],
            .pb-schedule-field input[type="number"] {
                box-sizing: border-box;
                width: 100%;
            }
            .pb-schedule-field input[type="number"] {
                width: 70px;
            }
            .pb-schedule-field .description {
                display: block;
                margin-top: 3px;
                font-size: 11px;
                color: #646970;
            }
            .pb-priority-row {
                display: flex;
                align-items: center;
                gap: 8px;
                flex-wrap: wrap;
            }
        </style>

        <div class="pb-schedule-field">
            <label for="pb_start_date"><?php esc_html_e( 'Start Date', 'promo-banner' ); ?></label>
            <input type="datetime-local" id="pb_start_date" name="pb_start_date"
                   value="<?php echo esc_attr( $start_date ); ?>" />
        </div>

        <div class="pb-schedule-field">
            <label for="pb_end_date"><?php esc_html_e( 'End Date', 'promo-banner' ); ?></label>
            <input type="datetime-local" id="pb_end_date" name="pb_end_date"
                   value="<?php echo esc_attr( $end_date ); ?>" />
        </div>

        <div class="pb-schedule-field">
            <label for="pb_priority"><?php esc_html_e( 'Priority', 'promo-banner' ); ?></label>
            <div class="pb-priority-row">
                <input type="number" id="pb_priority" name="pb_priority"
                       value="<?php echo esc_attr( $priority ); ?>" min="1" max="100" />
                <span class="description"><?php esc_html_e( 'Lower = higher priority', 'promo-banner' ); ?></span>
            </div>
        </div>
        <?php
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Shortcode Info Meta Box
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Renders the Shortcode reference meta box.
     *
     * @param WP_Post $post Current post object.
     * @return void
     */
    public static function render_shortcode_box( $post ) {
        if ( ! $post->ID ) {
            return;
        }
        ?>
        <p><?php esc_html_e( 'Banner shortcode:', 'promo-banner' ); ?></p>
        <code class="pb-shortcode-copy" style="display:block;padding:8px;background:#f6f7f7;border:1px solid #ddd;border-radius:3px;font-size:13px;cursor:pointer;" title="<?php esc_attr_e( 'Click to copy', 'promo-banner' ); ?>">
            [promo_banner id="<?php echo esc_html( $post->ID ); ?>"]
        </code>
        <br>
        <p><?php esc_html_e( 'With position attribute:', 'promo-banner' ); ?></p>
        <code class="pb-shortcode-copy" style="display:block;padding:8px;background:#f6f7f7;border:1px solid #ddd;border-radius:3px;font-size:12px;cursor:pointer;" title="<?php esc_attr_e( 'Click to copy', 'promo-banner' ); ?>">
            [promo_banner id="<?php echo esc_html( $post->ID ); ?>" position="before_content"]
        </code>
        <p class="description" style="margin-top:8px;font-size:11px;">
            <?php esc_html_e( 'Position values: before_content, after_content, floating', 'promo-banner' ); ?>
        </p>
        <?php
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Save Meta
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Saves all meta box data for a promo_banner post.
     * Verifies nonce, autosave, and capability before proceeding.
     *
     * @param int $post_id Post ID being saved.
     * @return void
     */
    public static function save_meta( $post_id ) {
        if ( ! isset( $_POST['pb_nonce'] ) || ! wp_verify_nonce( $_POST['pb_nonce'], 'pb_save_meta' ) ) {
            return;
        }
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        // Text / URL fields.
        $text_fields = [
            'pb_subtitle'    => '_pb_subtitle',
            'pb_link_text'   => '_pb_link_text',
            'pb_link_url'    => '_pb_link_url',
            'pb_link_target' => '_pb_link_target',
            'pb_bg_color'    => '_pb_bg_color',
            'pb_text_color'  => '_pb_text_color',
            'pb_link_color'  => '_pb_link_color',
            'pb_animation'   => '_pb_animation',
            'pb_padding'     => '_pb_padding',
            'pb_start_date'  => '_pb_start_date',
            'pb_end_date'    => '_pb_end_date',
        ];

        foreach ( $text_fields as $field => $meta_key ) {
            if ( isset( $_POST[ $field ] ) ) {
                update_post_meta( $post_id, $meta_key, sanitize_text_field( wp_unslash( $_POST[ $field ] ) ) );
            }
        }

        // Numeric fields.
        $numeric_fields = [
            'pb_font_size'   => '_pb_font_size',
            'pb_cookie_days' => '_pb_cookie_days',
            'pb_bg_image_id' => '_pb_bg_image_id',
            'pb_priority'    => '_pb_priority',
        ];
        foreach ( $numeric_fields as $field => $meta_key ) {
            if ( isset( $_POST[ $field ] ) ) {
                update_post_meta( $post_id, $meta_key, absint( $_POST[ $field ] ) );
            }
        }

        // Checkbox fields.
        update_post_meta( $post_id, '_pb_dismissible', isset( $_POST['pb_dismissible'] ) ? 1 : 0 );
        update_post_meta( $post_id, '_pb_show_all',    isset( $_POST['pb_show_all']    ) ? 1 : 0 );

        // Array fields (multi-select).
        $array_fields = [
            'pb_locations'  => '_pb_locations',
            'pb_pages'      => '_pb_pages',
            'pb_categories' => '_pb_categories',
            'pb_woo_cats'   => '_pb_woo_cats',
        ];
        foreach ( $array_fields as $field => $meta_key ) {
            $val = isset( $_POST[ $field ] ) ? array_map( 'sanitize_text_field', (array) wp_unslash( $_POST[ $field ] ) ) : [];
            update_post_meta( $post_id, $meta_key, $val );
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    // List Table Columns
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Customises the column headers for the promo_banner list table.
     *
     * @param array $cols Default columns.
     * @return array Modified columns.
     */
    public static function custom_columns( $cols ) {
        return [
            'cb'        => $cols['cb'],
            'title'     => __( 'Title',     'promo-banner' ),
            'preview'   => __( 'Preview',   'promo-banner' ),
            'locations' => __( 'Locations', 'promo-banner' ),
            'shortcode' => __( 'Shortcode', 'promo-banner' ),
            'date'      => $cols['date'],
        ];
    }

    /**
     * Renders the custom column cells for the promo_banner list table.
     *
     * @param string $col     Column slug.
     * @param int    $post_id Current post ID.
     * @return void
     */
    public static function render_columns( $col, $post_id ) {
        switch ( $col ) {
            case 'preview':
                $bg    = get_post_meta( $post_id, '_pb_bg_color',   true ) ?: '#1a5276';
                $color = get_post_meta( $post_id, '_pb_text_color', true ) ?: '#fff';
                $sub   = get_post_meta( $post_id, '_pb_subtitle',   true );
                echo '<div style="background:' . esc_attr( $bg ) . ';color:' . esc_attr( $color ) . ';padding:6px 12px;border-radius:3px;font-size:11px;max-width:200px;">';
                echo '<strong>' . esc_html( get_the_title( $post_id ) ) . '</strong>';
                if ( $sub ) {
                    echo '<br><span>' . esc_html( $sub ) . '</span>';
                }
                echo '</div>';
                break;

            case 'locations':
                $locs = get_post_meta( $post_id, '_pb_locations', true ) ?: [];
                $labels = [
                    'after_header'       => 'After Header',
                    'before_footer'      => 'Before Footer',
                    'after_woo_notices'  => 'After WC Notices',
                    'before_woo_content' => 'Before WC Content',
                    'after_woo_content'  => 'After WC Content',
                    'before_woo_sidebar' => 'Before WC Sidebar',
                    'shortcode_only'     => 'Shortcode Only',
                ];
                $display = [];
                foreach ( (array) $locs as $loc ) {
                    $display[] = isset( $labels[ $loc ] ) ? $labels[ $loc ] : esc_html( $loc );
                }
                echo ! empty( $display ) ? implode( ', ', array_map( 'esc_html', $display ) ) : '—';
                break;

            case 'shortcode':
                echo '<code>[promo_banner id="' . esc_html( $post_id ) . '"]</code>';
                break;
        }
    }
}
