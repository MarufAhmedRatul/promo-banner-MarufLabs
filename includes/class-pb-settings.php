<?php
/**
 * Global Plugin Settings Page
 *
 * @package PromoBanner
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class PB_Settings
 *
 * Registers a Settings sub-page under the Promo Banners admin menu.
 * All options are stored in wp_options via the Settings API.
 */
class PB_Settings {

    /** Option group / page slug */
    const PAGE_SLUG   = 'pb-settings';
    const OPTION_GROUP = 'pb_settings_group';

    // ──────────────────────────────────────────────────────────────────────────
    // Init
    // ──────────────────────────────────────────────────────────────────────────

    public static function init() {
        add_action( 'admin_menu',       [ __CLASS__, 'add_settings_page' ] );
        add_action( 'admin_init',       [ __CLASS__, 'register_settings'  ] );
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_styles' ] );
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Menu Registration
    // ──────────────────────────────────────────────────────────────────────────

    public static function add_settings_page() {
        add_submenu_page(
            'edit.php?post_type=' . PB_POST_TYPE,
            __( 'Promo Banner Settings', 'promo-banner' ),
            __( 'Settings', 'promo-banner' ),
            'manage_options',
            self::PAGE_SLUG,
            [ __CLASS__, 'render_page' ]
        );
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Settings API Registration
    // ──────────────────────────────────────────────────────────────────────────

    public static function register_settings() {

        // ── Section: Performance ──────────────────────────────────────────────
        add_settings_section(
            'pb_section_performance',
            __( '⚡ Performance', 'promo-banner' ),
            '__return_false',
            self::PAGE_SLUG
        );

        register_setting( self::OPTION_GROUP, 'pb_load_assets', [
            'type'              => 'string',
            'sanitize_callback' => [ __CLASS__, 'sanitize_load_assets' ],
            'default'           => 'always',
        ] );
        add_settings_field(
            'pb_load_assets',
            __( 'Load CSS & JS', 'promo-banner' ),
            [ __CLASS__, 'field_load_assets' ],
            self::PAGE_SLUG,
            'pb_section_performance'
        );

        // ── Section: Display Defaults ─────────────────────────────────────────
        add_settings_section(
            'pb_section_defaults',
            __( '🎨 Display Defaults', 'promo-banner' ),
            '__return_false',
            self::PAGE_SLUG
        );

        register_setting( self::OPTION_GROUP, 'pb_mobile_enabled', [
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '1',
        ] );
        add_settings_field(
            'pb_mobile_enabled',
            __( 'Show Banners on Mobile', 'promo-banner' ),
            [ __CLASS__, 'field_mobile_enabled' ],
            self::PAGE_SLUG,
            'pb_section_defaults'
        );

        register_setting( self::OPTION_GROUP, 'pb_default_animation', [
            'type'              => 'string',
            'sanitize_callback' => [ __CLASS__, 'sanitize_animation' ],
            'default'           => 'slide_down',
        ] );
        add_settings_field(
            'pb_default_animation',
            __( 'Default Animation', 'promo-banner' ),
            [ __CLASS__, 'field_default_animation' ],
            self::PAGE_SLUG,
            'pb_section_defaults'
        );

        register_setting( self::OPTION_GROUP, 'pb_default_cookie_days', [
            'type'              => 'integer',
            'sanitize_callback' => 'absint',
            'default'           => 1,
        ] );
        add_settings_field(
            'pb_default_cookie_days',
            __( 'Default Cookie Duration (days)', 'promo-banner' ),
            [ __CLASS__, 'field_default_cookie_days' ],
            self::PAGE_SLUG,
            'pb_section_defaults'
        );

        // ── Section: Advanced / Danger Zone ──────────────────────────────────
        add_settings_section(
            'pb_section_advanced',
            __( '⚠️ Advanced', 'promo-banner' ),
            [ __CLASS__, 'section_advanced_desc' ],
            self::PAGE_SLUG
        );

        register_setting( self::OPTION_GROUP, 'pb_uninstall_data', [
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '0',
        ] );
        add_settings_field(
            'pb_uninstall_data',
            __( 'Delete All Data on Uninstall', 'promo-banner' ),
            [ __CLASS__, 'field_uninstall_data' ],
            self::PAGE_SLUG,
            'pb_section_advanced'
        );
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Field Renderers
    // ──────────────────────────────────────────────────────────────────────────

    public static function field_load_assets() {
        $val = get_option( 'pb_load_assets', 'always' );
        ?>
        <fieldset>
            <label>
                <input type="radio" name="pb_load_assets" value="always" <?php checked( $val, 'always' ); ?> />
                <?php esc_html_e( 'On every page (recommended for dynamic sites)', 'promo-banner' ); ?>
            </label><br>
            <label>
                <input type="radio" name="pb_load_assets" value="smart" <?php checked( $val, 'smart' ); ?> />
                <?php esc_html_e( 'Only when an active banner exists (saves HTTP requests)', 'promo-banner' ); ?>
            </label>
        </fieldset>
        <?php
    }

    public static function field_mobile_enabled() {
        $val = get_option( 'pb_mobile_enabled', '1' );
        ?>
        <label>
            <input type="checkbox" name="pb_mobile_enabled" value="1" <?php checked( $val, '1' ); ?> />
            <?php esc_html_e( 'Display promo banners on mobile devices', 'promo-banner' ); ?>
        </label>
        <?php
    }

    public static function field_default_animation() {
        $val = get_option( 'pb_default_animation', 'slide_down' );
        $options = [
            'slide_down' => __( 'Slide Down', 'promo-banner' ),
            'slide_up'   => __( 'Slide Up', 'promo-banner' ),
            'fade_in'    => __( 'Fade In', 'promo-banner' ),
            'none'       => __( 'None', 'promo-banner' ),
        ];
        ?>
        <select name="pb_default_animation">
            <?php foreach ( $options as $key => $label ) : ?>
                <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $val, $key ); ?>>
                    <?php echo esc_html( $label ); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <p class="description"><?php esc_html_e( 'Applied to new banners by default. Can be overridden per banner.', 'promo-banner' ); ?></p>
        <?php
    }

    public static function field_default_cookie_days() {
        $val = (int) get_option( 'pb_default_cookie_days', 1 );
        ?>
        <input type="number" name="pb_default_cookie_days" value="<?php echo esc_attr( $val ); ?>"
               min="0" max="365" style="width:80px;" />
        <span><?php esc_html_e( 'days', 'promo-banner' ); ?></span>
        <p class="description"><?php esc_html_e( 'How long a dismissed banner stays hidden. 0 = hide for current session only.', 'promo-banner' ); ?></p>
        <?php
    }

    public static function section_advanced_desc() {
        echo '<p style="color:#b32d2e;">' . esc_html__( 'These settings affect plugin data. Proceed with caution.', 'promo-banner' ) . '</p>';
    }

    public static function field_uninstall_data() {
        $val = get_option( 'pb_uninstall_data', '0' );
        ?>
        <label>
            <input type="checkbox" name="pb_uninstall_data" value="1" <?php checked( $val, '1' ); ?> />
            <?php esc_html_e( 'When the plugin is deleted, permanently remove all banner posts, meta data, and settings.', 'promo-banner' ); ?>
        </label>
        <p class="description" style="color:#b32d2e;">
            <?php esc_html_e( '⚠️ This action is irreversible. All your banners will be permanently deleted.', 'promo-banner' ); ?>
        </p>
        <?php
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Sanitization Callbacks
    // ──────────────────────────────────────────────────────────────────────────

    public static function sanitize_load_assets( $value ) {
        return in_array( $value, [ 'always', 'smart' ], true ) ? $value : 'always';
    }

    public static function sanitize_animation( $value ) {
        $allowed = [ 'slide_down', 'slide_up', 'fade_in', 'none' ];
        return in_array( $value, $allowed, true ) ? $value : 'slide_down';
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Page Renderer
    // ──────────────────────────────────────────────────────────────────────────

    public static function render_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        ?>
        <div class="wrap pb-settings-wrap">
            <h1 class="pb-settings-title">
                <span class="dashicons dashicons-megaphone" style="font-size:28px;line-height:1.2;color:#2271b1;margin-right:8px;"></span>
                <?php esc_html_e( 'Promo Banner — Settings', 'promo-banner' ); ?>
            </h1>

            <div class="pb-settings-info">
                <strong><?php esc_html_e( 'Plugin Version:', 'promo-banner' ); ?></strong>
                <?php echo esc_html( PB_VERSION ); ?>
                &nbsp;&nbsp;|&nbsp;&nbsp;
                <a href="https://maruflabs.com/promo-banner" target="_blank" rel="noopener noreferrer">
                    <?php esc_html_e( 'Documentation', 'promo-banner' ); ?> ↗
                </a>
                &nbsp;&nbsp;|&nbsp;&nbsp;
                <a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=' . PB_POST_TYPE ) ); ?>">
                    <?php esc_html_e( '+ Create New Banner', 'promo-banner' ); ?>
                </a>
            </div>

            <form method="post" action="options.php" id="pb-settings-form">
                <?php
                settings_fields( self::OPTION_GROUP );
                do_settings_sections( self::PAGE_SLUG );
                submit_button( __( 'Save Settings', 'promo-banner' ) );
                ?>
            </form>
        </div>
        <?php
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Inline Styles for Settings Page
    // ──────────────────────────────────────────────────────────────────────────

    public static function enqueue_styles( $hook ) {
        // Only load on our settings page.
        if ( strpos( $hook, self::PAGE_SLUG ) === false ) {
            return;
        }
        ?>
        <style>
            .pb-settings-wrap { max-width: 760px; }
            .pb-settings-title { display: flex; align-items: center; margin-bottom: 6px; }
            .pb-settings-info {
                background: #f0f6ff;
                border-left: 4px solid #2271b1;
                padding: 10px 16px;
                margin-bottom: 20px;
                border-radius: 0 4px 4px 0;
                font-size: 13px;
            }
            #pb-settings-form .form-table th { width: 240px; }
            #pb-settings-form h2 {
                background: #f6f7f7;
                padding: 8px 14px;
                border-left: 3px solid #2271b1;
                font-size: 14px;
                margin-top: 24px;
            }
        </style>
        <?php
    }
}
