<?php
/**
 * Uninstall Script — Promo Banner for WooCommerce
 *
 * This file is executed automatically by WordPress when the plugin is deleted
 * from the Plugins admin screen. It calls PB_Installer::uninstall() to
 * perform any necessary database cleanup.
 *
 * ⚠ Data is only deleted when the user has enabled the
 *   "Delete All Data on Uninstall" option in Plugin Settings.
 *
 * @package PromoBanner
 * @since   1.0.0
 */

// WordPress safety check — prevent direct access.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Load only the constants and the installer class — nothing else is needed.
define( 'PB_VERSION',   '1.0.0' );
define( 'PB_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'PB_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'PB_POST_TYPE',  'promo_banner' );

require_once PB_PLUGIN_DIR . 'includes/class-pb-post-type.php';
require_once PB_PLUGIN_DIR . 'includes/class-pb-installer.php';

PB_Installer::uninstall();
