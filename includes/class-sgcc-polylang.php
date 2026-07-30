<?php
/**
 * Polylang Integration
 *
 * Registers all translatable strings with Polylang.
 *
 * @package SchonGeil_Cookie_Consent
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SGCC_Polylang {

    /**
     * Singleton instance.
     *
     * @var SGCC_Polylang|null
     */
    private static $instance = null;

    /**
     * Get singleton instance.
     *
     * @return SGCC_Polylang
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor.
     */
    private function __construct() {
        if ( ! function_exists( 'pll_register_string' ) ) {
            return;
        }

        add_action( 'init', array( $this, 'register_strings' ) );
    }

    /**
     * Register all translatable strings with Polylang.
     */
    public function register_strings() {
        $group = 'schongeil.de Cookie Consent';

        // Banner texts.
        pll_register_string( 'sgcc_banner_title', 'Cookie-Einstellungen', $group );
        pll_register_string( 'sgcc_banner_description', 'Diese Website nutzt eingebettete Inhalte von Drittanbietern (z.B. YouTube, SoundCloud). Beim Laden dieser Inhalte werden Daten an die jeweiligen Anbieter übermittelt.', $group );
        pll_register_string( 'sgcc_btn_accept_all', 'Alle akzeptieren', $group );
        pll_register_string( 'sgcc_btn_reject', 'Nur notwendige', $group );
        pll_register_string( 'sgcc_btn_settings', 'Einstellungen', $group );

        // Popup texts.
        pll_register_string( 'sgcc_popup_title', 'Cookie-Einstellungen', $group );
        pll_register_string( 'sgcc_popup_description', 'Hier kannst du einstellen, welche Arten von Cookies und eingebetteten Inhalten du zulassen möchtest.', $group );

        // Category texts (dynamic from Services registry).
        $categories = SGCC_Services::get_default_categories();
        foreach ( $categories as $cat_key => $cat ) {
            pll_register_string( "sgcc_cat_{$cat_key}_name", $cat['name_de'], $group );
            pll_register_string( "sgcc_cat_{$cat_key}_desc", $cat['desc_de'], $group );
        }

        pll_register_string( 'sgcc_always_active', 'Immer aktiv', $group );
        pll_register_string( 'sgcc_btn_save', 'Auswahl speichern', $group );

        // Placeholder texts.
        pll_register_string( 'sgcc_load_content', 'Inhalt laden', $group );
        pll_register_string( 'sgcc_privacy_link', 'Datenverarbeitungserklärung', $group );

        // Floating icon.
        pll_register_string( 'sgcc_floating_label', 'Cookie-Einstellungen ändern', $group );
        pll_register_string( 'sgcc_floating_tooltip', 'Klicke hier, um deine Cookie-Einstellungen für eingebettete Inhalte wie Videos und Musik-Player anzupassen.', $group );

        // Service-specific texts.
        $services = SGCC_Services::get_defaults();
        foreach ( $services as $key => $service ) {
            if ( isset( $service['texts']['de'] ) ) {
                foreach ( $service['texts']['de'] as $text_key => $text_value ) {
                    pll_register_string( "sgcc_service_{$key}_{$text_key}", $text_value, $group );
                }
            }
        }
    }
}
