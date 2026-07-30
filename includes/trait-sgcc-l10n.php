<?php
/**
 * Shared localization helpers.
 *
 * Used by frontend-facing classes that need the current language,
 * translatable texts and the privacy page URL.
 *
 * @package SchonGeil_Cookie_Consent
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

trait SGCC_L10n {

    /**
     * Get the current language slug ('de' or 'en').
     *
     * @return string
     */
    private function get_current_lang() {
        if ( function_exists( 'pll_current_language' ) ) {
            $lang = pll_current_language( 'slug' );
            return ( 'en' === $lang ) ? 'en' : 'de';
        }
        return 'de';
    }

    /**
     * Get a translatable text.
     *
     * With Polylang the German default acts as the registered string;
     * untranslated strings fall back to the English default for 'en'.
     * Without Polylang, custom texts from the settings take precedence.
     *
     * @param string $key        Text key in the sgcc_texts option.
     * @param string $default_de German default.
     * @param string $default_en English default.
     * @return string
     */
    private function get_text( $key, $default_de, $default_en ) {
        $lang = $this->get_current_lang();

        if ( function_exists( 'pll__' ) ) {
            $translated = pll__( $default_de );
            if ( $translated !== $default_de || 'de' === $lang ) {
                return $translated;
            }
            return $default_en;
        }

        $custom_texts = get_option( 'sgcc_texts', array() );
        if ( ! empty( $custom_texts[ $lang ][ $key ] ) ) {
            return $custom_texts[ $lang ][ $key ];
        }
        return ( 'en' === $lang ) ? $default_en : $default_de;
    }

    /**
     * Get the privacy page URL for the current language.
     *
     * Falls back to the other language's page, then to the WP privacy page.
     *
     * @return string URL or empty string.
     */
    private function get_privacy_page_url() {
        $lang    = $this->get_current_lang();
        $page_id = absint( get_option( 'sgcc_privacy_page_id_' . $lang, 0 ) );

        if ( ! $page_id ) {
            $fallback_lang = ( 'en' === $lang ) ? 'de' : 'en';
            $page_id       = absint( get_option( 'sgcc_privacy_page_id_' . $fallback_lang, 0 ) );
        }

        if ( ! $page_id ) {
            $page_id = absint( get_option( 'wp_page_for_privacy_policy', 0 ) );
        }

        return $page_id ? (string) get_permalink( $page_id ) : '';
    }
}
