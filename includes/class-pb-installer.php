<?php
/**
 * Plugin Installer — Activation, Deactivation & Uninstall Routines
 *
 * @package PromoBanner
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class PB_Installer
 *
 * Handles all lifecycle events for the Promo Banner plugin:
 *  - register_activation_hook   → PB_Installer::activate()
 *  - register_deactivation_hook → PB_Installer::deactivate()
 *  - uninstall.php              → PB_Installer::uninstall()
 */
class PB_Installer {

    /**
     * Default global plugin options stored in wp_options.
     *
     * @var array
     */
    private static $default_options = [
        'pb_version'             => PB_VERSION,
        'pb_load_assets'         => 'always',   // 'always' | 'smart' (only when banner exists)
        'pb_mobile_enabled'      => '1',
        'pb_default_animation'   => 'slide_down',
        'pb_default_cookie_days' => '1',
        'pb_uninstall_data'      => '0',         // '1' = delete all data on uninstall
    ];

    // ──────────────────────────────────────────────────────────────────────────
    // Activation
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Runs when the plugin is activated.
     *  - Stores the installed version.
     *  - Seeds default options (skips if already set).
     *  - Flushes rewrite rules so the CPT is available immediately.
     *  - Sets an activation notice transient.
     *
     * @return void
     */
    public static function activate() {
        // Register CPT & taxonomy first so flush_rewrite_rules works correctly.
        PB_Post_Type::register_post_type();
        PB_Post_Type::register_taxonomy();

        // Store / update the installed version.
        update_option( 'pb_version', PB_VERSION, false );

        // Seed default options (only if not already present).
        foreach ( self::$default_options as $key => $value ) {
            if ( false === get_option( $key ) ) {
                add_option( $key, $value, '', false );
            }
        }

        // Show a one-time admin notice after activation.
        set_transient( 'pb_activation_notice', true, 5 );

        // Flush rewrite rules so the CPT permalink works immediately.
        flush_rewrite_rules();
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Deactivation
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Runs when the plugin is deactivated.
     * Safe operation — only flushes rewrite rules; no data is deleted.
     *
     * @return void
     */
    public static function deactivate() {
        flush_rewrite_rules();
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Uninstall
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Runs when the plugin is deleted from WP Admin → Plugins.
     * Called from uninstall.php.
     *
     * ⚠ Only deletes data when the user has opted in via Settings
     *   (pb_uninstall_data = '1'). This prevents accidental data loss.
     *
     * @return void
     */
    public static function uninstall() {
        // Respect the "Delete all data on uninstall" setting.
        if ( '1' !== get_option( 'pb_uninstall_data', '0' ) ) {
            // Still clean up our own options regardless.
            self::delete_options();
            return;
        }

        self::delete_all_banners();
        self::delete_options();
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Private Helpers
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Permanently deletes every promo_banner post and its post-meta.
     *
     * @return void
     */
    private static function delete_all_banners() {
        global $wpdb;

        // Fetch all promo_banner post IDs.
        $post_ids = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT ID FROM {$wpdb->posts} WHERE post_type = %s",
                PB_POST_TYPE
            )
        );

        if ( ! empty( $post_ids ) ) {
            // Delete post-meta rows directly — faster than wp_delete_post() loop.
            $id_placeholders = implode( ', ', array_fill( 0, count( $post_ids ), '%d' ) );

            // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
            $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM {$wpdb->postmeta} WHERE post_id IN ($id_placeholders)",
                    $post_ids
                )
            );

            // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
            $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM {$wpdb->posts} WHERE ID IN ($id_placeholders)",
                    $post_ids
                )
            );
        }

        // Remove banner_group term relationships, terms, and taxonomy row.
        $terms = get_terms( [
            'taxonomy'   => 'banner_group',
            'hide_empty' => false,
            'fields'     => 'ids',
        ] );

        if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
            foreach ( $terms as $term_id ) {
                wp_delete_term( $term_id, 'banner_group' );
            }
        }
    }

    /**
     * Removes all plugin options from wp_options.
     *
     * @return void
     */
    private static function delete_options() {
        foreach ( array_keys( self::$default_options ) as $key ) {
            delete_option( $key );
        }
        // Also clean up the version key (stored separately on activate).
        delete_option( 'pb_version' );

        // Clean up any residual transients.
        delete_transient( 'pb_activation_notice' );
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Activation Admin Notice
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Displays a one-time welcome notice after plugin activation.
     * Hooked to admin_notices in the main plugin file.
     *
     * @return void
     */
    public static function activation_notice() {
        if ( ! get_transient( 'pb_activation_notice' ) ) {
            return;
        }

        delete_transient( 'pb_activation_notice' );

        $settings_url = admin_url( 'edit.php?post_type=' . PB_POST_TYPE . '&page=pb-settings' );
        $new_url      = admin_url( 'post-new.php?post_type=' . PB_POST_TYPE );
        ?>
        <div class="notice notice-success is-dismissible pb-activation-notice">
            <p>
                <strong>🎉 Promo Banner for WooCommerce</strong> has been activated successfully!
                &nbsp;
                <a href="<?php echo esc_url( $new_url ); ?>" class="button button-primary">
                    Create Your First Banner
                </a>
                &nbsp;
                <a href="<?php echo esc_url( $settings_url ); ?>" class="button">
                    Plugin Settings
                </a>
            </p>
        </div>
        <?php
    }
}
