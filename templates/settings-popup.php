<?php
/**
 * Settings Popup Template
 *
 * @package SchonGeil_Cookie_Consent
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$grouped = SGCC_Services::get_enabled_by_category();
$all_categories = SGCC_Services::get_default_categories();
?>
<div id="sgcc-popup-overlay" class="sgcc-popup-overlay" role="dialog" aria-modal="true" aria-label="<?php echo esc_attr( $texts['title'] ); ?>" aria-hidden="true">
    <div class="sgcc-popup">
        <div class="sgcc-popup__header">
            <h2 class="sgcc-popup__title"><?php echo esc_html( $texts['title'] ); ?></h2>
            <p class="sgcc-popup__description"><?php echo esc_html( $texts['description'] ); ?></p>
            <button class="sgcc-popup__close" type="button" data-sgcc-action="close-popup" aria-label="<?php esc_attr_e( 'Close', 'sgcc' ); ?>">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>

        <div class="sgcc-popup__body">
            <?php
            // Determine display order: necessary first, then audio, then video.
            $category_order = array( 'necessary', 'audio', 'video' );
            foreach ( $category_order as $cat_key ) :
                if ( ! isset( $all_categories[ $cat_key ] ) ) continue;
                $cat = $all_categories[ $cat_key ];
                $cat_name = $cat[ 'name_' . $lang ] ?? $cat['name_de'];
                $cat_desc = $cat[ 'desc_' . $lang ] ?? $cat['desc_de'];
                $is_required = ! empty( $cat['required'] );
                $cat_services = $grouped[ $cat_key ] ?? array();
            ?>
                <div class="sgcc-popup__category" data-sgcc-category="<?php echo esc_attr( $cat_key ); ?>">
                    <div class="sgcc-popup__category-header">
                        <h3 class="sgcc-popup__category-name"><?php echo esc_html( $cat_name ); ?></h3>
                        <?php if ( $is_required ) : ?>
                            <span class="sgcc-popup__always-active"><?php echo esc_html( $texts['always_active'] ); ?></span>
                        <?php else : ?>
                            <label class="sgcc-toggle">
                                <input class="sgcc-toggle__input sgcc-category-toggle" type="checkbox" data-sgcc-category="<?php echo esc_attr( $cat_key ); ?>" />
                                <span class="sgcc-toggle__slider"></span>
                            </label>
                        <?php endif; ?>
                    </div>
                    <div class="sgcc-popup__category-body">
                        <p class="sgcc-popup__category-desc"><?php echo esc_html( $cat_desc ); ?></p>

                        <?php if ( $is_required && ! empty( $cookies ) ) : ?>
                            <div class="sgcc-popup__cookie-list">
                                <?php foreach ( $cookies as $cookie ) :
                                    if ( ( $cookie['category'] ?? '' ) !== 'necessary' ) continue;
                                    $cookie_desc = $cookie[ 'description_' . $lang ] ?? $cookie['description_de'] ?? '';
                                ?>
                                    <div class="sgcc-popup__cookie-item">
                                        <div class="sgcc-popup__cookie-name"><code><?php echo esc_html( $cookie['name'] ?? '' ); ?></code></div>
                                        <div class="sgcc-popup__cookie-meta">
                                            <?php if ( ! empty( $cookie['provider'] ) ) : ?>
                                                <span><?php echo esc_html( $cookie['provider'] ); ?></span>
                                            <?php endif; ?>
                                            <?php if ( ! empty( $cookie['type'] ) ) : ?>
                                                <span><?php echo esc_html( $cookie['type'] ); ?></span>
                                            <?php endif; ?>
                                            <?php if ( ! empty( $cookie['duration'] ) ) : ?>
                                                <span><?php echo esc_html( $cookie['duration'] ); ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ( $cookie_desc ) : ?>
                                            <div class="sgcc-popup__cookie-desc"><?php echo esc_html( $cookie_desc ); ?></div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php if ( ! $is_required && ! empty( $cat_services ) ) : ?>
                            <div class="sgcc-popup__services-list">
                                <?php foreach ( $cat_services as $svc_key => $svc ) : ?>
                                    <div class="sgcc-popup__service-item">
                                        <label class="sgcc-popup__service-toggle">
                                            <input class="sgcc-popup__service-checkbox sgcc-service-toggle" type="checkbox" data-sgcc-service="<?php echo esc_attr( $svc_key ); ?>" data-sgcc-category="<?php echo esc_attr( $cat_key ); ?>" />
                                            <span class="sgcc-popup__service-name"><?php echo esc_html( $svc['name'] ); ?></span>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="sgcc-popup__footer">
            <div class="sgcc-popup__footer-buttons">
                <button class="sgcc-popup__btn sgcc-popup__btn--save" type="button" data-sgcc-action="save-settings">
                    <?php echo esc_html( $texts['save'] ); ?>
                </button>
                <button class="sgcc-popup__btn sgcc-popup__btn--accept" type="button" data-sgcc-action="popup-accept-all">
                    <?php echo esc_html( $texts['accept_all'] ); ?>
                </button>
            </div>
            <?php if ( ! empty( $privacy_url ) ) : ?>
                <div class="sgcc-popup__footer-link">
                    <a href="<?php echo esc_url( $privacy_url ); ?>"><?php echo esc_html( $texts['privacy_link'] ); ?></a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
