<?php
/**
 * Cookie Banner Template
 *
 * @package SchonGeil_Cookie_Consent
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div id="sgcc-banner" class="sgcc-banner sgcc-banner--<?php echo esc_attr( $position ); ?>" role="dialog" aria-label="<?php echo esc_attr( $texts['title'] ); ?>" aria-hidden="true" tabindex="-1">
    <div class="sgcc-banner__inner">
        <div class="sgcc-banner__text">
            <h2 class="sgcc-banner__title"><?php echo esc_html( $texts['title'] ); ?></h2>
            <p class="sgcc-banner__description"><?php echo esc_html( $texts['description'] ); ?></p>
            <div class="sgcc-banner__links">
                <?php if ( ! empty( $privacy_url ) ) : ?>
                    <a class="sgcc-banner__link" href="<?php echo esc_url( $privacy_url ); ?>"><?php echo esc_html( $texts['privacy_link'] ); ?></a>
                <?php endif; ?>
                <?php if ( ! empty( $custom_link_url ) && ! empty( $custom_link_text ) ) : ?>
                    <a class="sgcc-banner__link" href="<?php echo esc_url( $custom_link_url ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $custom_link_text ); ?></a>
                <?php endif; ?>
            </div>
        </div>
        <div class="sgcc-banner__actions" role="group" aria-label="<?php echo esc_attr( $texts['title'] ); ?>">
            <button class="sgcc-banner__btn sgcc-banner__btn--accept" type="button" data-sgcc-action="accept-all">
                <?php echo esc_html( $texts['accept_all'] ); ?>
            </button>
            <button class="sgcc-banner__btn sgcc-banner__btn--reject" type="button" data-sgcc-action="reject-all">
                <?php echo esc_html( $texts['reject'] ); ?>
            </button>
            <button class="sgcc-banner__btn sgcc-banner__btn--settings" type="button" data-sgcc-action="open-settings">
                <?php echo esc_html( $texts['settings'] ); ?>
            </button>
        </div>
    </div>
</div>
