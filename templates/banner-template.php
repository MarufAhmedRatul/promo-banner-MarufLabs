<?php
/**
 * Promo Banner Template
 *
 * Renders a single promo banner HTML element.
 * This file is loaded via include from PB_Frontend::render_banner().
 *
 * Available variables:
 *   int    $banner_id      — Post ID of the banner.
 *   string $title          — Banner headline.
 *   string $subtitle       — Optional sub-headline / body text.
 *   string $link_text      — Call-to-action button label.
 *   string $link_url       — Call-to-action button URL.
 *   string $link_target    — '_self' or '_blank'.
 *   string $bg_color       — CSS background color (hex/rgb).
 *   string $text_color     — CSS text color.
 *   string $link_color     — CSS color for the CTA link/button.
 *   string $bg_style       — Full inline background CSS string.
 *   string $animation      — Animation type slug (slide_down, slide_up, fade_in, none).
 *   bool   $dismissible    — Whether the close button is shown.
 *   int    $cookie_days    — Days to suppress after dismissal.
 *   string $padding        — CSS padding value.
 *   int    $font_size      — Title font size in px.
 *   array  $wrapper_classes — CSS classes for the outer wrapper element.
 *   string $position       — Optional position shortcode variant.
 *
 * @package PromoBanner
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div
    class="<?php echo esc_attr( implode( ' ', array_filter( $wrapper_classes ) ) ); ?>"
    id="pb-banner-<?php echo esc_attr( $banner_id ); ?>"
    style="<?php echo esc_attr( $bg_style ); ?>color:<?php echo esc_attr( $text_color ); ?>;padding:<?php echo esc_attr( $padding ); ?>;"
    data-banner-id="<?php echo esc_attr( $banner_id ); ?>"
    data-cookie-days="<?php echo esc_attr( $cookie_days ); ?>"
    data-animation="<?php echo esc_attr( $animation ); ?>"
    role="banner"
    aria-label="<?php echo esc_attr( $title ); ?>"
>
    <?php if ( $dismissible ) : ?>
    <button
        class="pb-close-btn"
        aria-label="<?php esc_attr_e( 'Close banner', 'promo-banner' ); ?>"
        style="color:<?php echo esc_attr( $text_color ); ?>;"
    >&times;</button>
    <?php endif; ?>

    <div class="pb-banner-inner">
        <?php if ( $title ) : ?>
        <h3 class="pb-title" style="font-size:<?php echo esc_attr( $font_size ); ?>px;color:<?php echo esc_attr( $text_color ); ?>;">
            <?php echo esc_html( $title ); ?>
        </h3>
        <?php endif; ?>

        <?php if ( $subtitle ) : ?>
        <p class="pb-subtitle" style="color:<?php echo esc_attr( $text_color ); ?>;">
            <?php echo esc_html( $subtitle ); ?>
        </p>
        <?php endif; ?>

        <?php if ( $link_text && $link_url ) : ?>
        <a
            class="pb-link"
            href="<?php echo esc_url( $link_url ); ?>"
            target="<?php echo esc_attr( $link_target ); ?>"
            style="color:<?php echo esc_attr( $link_color ); ?>;border-bottom-color:<?php echo esc_attr( $link_color ); ?>;"
            <?php echo '_blank' === $link_target ? 'rel="noopener noreferrer"' : ''; ?>
        >
            <?php echo esc_html( $link_text ); ?>
        </a>
        <?php endif; ?>
    </div>
</div>
