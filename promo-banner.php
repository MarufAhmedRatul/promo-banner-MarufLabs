<?php
/**
 * Plugin Name:       Promo Banner for WooCommerce — MarufLabs
 * Plugin URI:        https://maruflabs.com/promo-banner
 * Description:       Animated slide-down promo banners for WooCommerce. Supports shortcodes, page targeting, category targeting, and flexible display location hooks.
 * Version:           1.0.0
 * Requires at least: 5.9
 * Requires PHP:      7.4
 * Author:            Maruf Ahmed
 * Author URI:        https://maruflabs.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       promo-banner
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ─── Constants ────────────────────────────────────────────────────────────────
define( 'PB_VERSION',    '1.0.0' );
define( 'PB_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'PB_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'PB_POST_TYPE',  'promo_banner' );

// ─── Autoload Includes ─────────────────────────────────────────────────────────
require_once PB_PLUGIN_DIR . 'includes/class-pb-post-type.php';
require_once PB_PLUGIN_DIR . 'includes/class-pb-installer.php';
require_once PB_PLUGIN_DIR . 'includes/class-pb-admin.php';
require_once PB_PLUGIN_DIR . 'includes/class-pb-frontend.php';
require_once PB_PLUGIN_DIR . 'includes/class-pb-shortcode.php';
require_once PB_PLUGIN_DIR . 'includes/class-pb-settings.php';

// ─── Lifecycle Hooks ───────────────────────────────────────────────────────────
register_activation_hook(   __FILE__, [ 'PB_Installer', 'activate'   ] );
register_deactivation_hook( __FILE__, [ 'PB_Installer', 'deactivate' ] );

// ─── Boot ─────────────────────────────────────────────────────────────────────
/**
 * Initialises all plugin components.
 * Fires on 'plugins_loaded' to ensure WordPress is fully bootstrapped.
 */
function pb_init() {
    PB_Post_Type::init();
    PB_Admin::init();
    PB_Frontend::init();
    PB_Shortcode::init();
    PB_Settings::init();

    // Display one-time activation notice.
    add_action( 'admin_notices', [ 'PB_Installer', 'activation_notice' ] );
}
add_action( 'plugins_loaded', 'pb_init' );
