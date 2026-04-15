<?php
/**
 * Custom Post Type & Taxonomy Registration — Promo Banner
 *
 * @package PromoBanner
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class PB_Post_Type
 *
 * Registers the `promo_banner` custom post type and
 * the `banner_group` taxonomy used for optional grouping.
 */
class PB_Post_Type {

    public static function init() {
        add_action( 'init', [ __CLASS__, 'register_post_type' ] );
        add_action( 'init', [ __CLASS__, 'register_taxonomy'  ] );
    }

    /**
     * Register the promo_banner Custom Post Type.
     *
     * @return void
     */
    public static function register_post_type() {
        $labels = [
            'name'               => __( 'Promo Banners',         'promo-banner' ),
            'singular_name'      => __( 'Promo Banner',          'promo-banner' ),
            'add_new'            => __( 'Add New Banner',        'promo-banner' ),
            'add_new_item'       => __( 'Add New Promo Banner',  'promo-banner' ),
            'edit_item'          => __( 'Edit Banner',           'promo-banner' ),
            'new_item'           => __( 'New Banner',            'promo-banner' ),
            'view_item'          => __( 'View Banner',           'promo-banner' ),
            'all_items'          => __( 'All Banners',           'promo-banner' ),
            'search_items'       => __( 'Search Banners',        'promo-banner' ),
            'not_found'          => __( 'No banners found.',     'promo-banner' ),
            'not_found_in_trash' => __( 'No banners in Trash.', 'promo-banner' ),
            'menu_name'          => __( 'Promo Banners',         'promo-banner' ),
        ];

        register_post_type( PB_POST_TYPE, [
            'labels'          => $labels,
            'public'          => false,
            'show_ui'         => true,
            'show_in_menu'    => true,
            'menu_icon'       => 'dashicons-megaphone',
            'menu_position'   => 56,
            'supports'        => [ 'title' ],
            'capability_type' => 'post',
            'rewrite'         => false,
        ] );
    }

    /**
     * Register the banner_group taxonomy for optional banner grouping.
     *
     * @return void
     */
    public static function register_taxonomy() {
        register_taxonomy( 'banner_group', PB_POST_TYPE, [
            'label'        => __( 'Banner Groups', 'promo-banner' ),
            'hierarchical' => true,
            'show_ui'      => true,
            'show_in_menu' => true,
            'rewrite'      => false,
        ] );
    }
}
