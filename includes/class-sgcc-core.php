<?php
/**
 * Core Plugin Class
 *
 * Initializes all components.
 *
 * @package SchonGeil_Cookie_Consent
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SGCC_Core {

    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->load_textdomain();
        $this->init_components();
        $this->inject_custom_colors();
    }

    private function load_textdomain() {
        load_plugin_textdomain( 'sgcc', false, dirname( SGCC_PLUGIN_BASENAME ) . '/languages' );
    }

    private function init_components() {
        if ( is_admin() ) {
            SGCC_Admin::get_instance();
        }

        if ( ! is_admin() ) {
            SGCC_Blocker::get_instance();
            SGCC_Frontend::get_instance();
        }

        SGCC_Consent_Log::get_instance();
        SGCC_Polylang::get_instance();
    }

    /**
     * Inject custom CSS variables from admin settings.
     */
    private function inject_custom_colors() {
        if ( is_admin() ) {
            return;
        }

        add_action( 'wp_head', function () {
            $primary          = get_option( 'sgcc_primary_color', '#1a1a2e' );
            $bg               = get_option( 'sgcc_background_color', '#ffffff' );
            $btn_bg           = get_option( 'sgcc_button_bg_color', '#16213e' );
            $btn_text         = get_option( 'sgcc_button_text_color', '#ffffff' );
            $btn_border       = get_option( 'sgcc_button_border_color', '#16213e' );
            $btn_hover_bg     = get_option( 'sgcc_button_hover_bg_color', '#ffffff' );
            $btn_hover_text   = get_option( 'sgcc_button_hover_text_color', '#16213e' );
            $btn_hover_border = get_option( 'sgcc_button_hover_border_color', '#16213e' );
            $float_bg         = get_option( 'sgcc_floating_icon_bg_color', '#1a1a2e' );
            $float_text       = get_option( 'sgcc_floating_icon_text_color', '#ffffff' );

            // Validate all color values are proper hex before output to prevent CSS injection.
            $colors = compact( 'primary', 'bg', 'btn_bg', 'btn_text', 'btn_border', 'btn_hover_bg', 'btn_hover_text', 'btn_hover_border', 'float_bg', 'float_text' );
            foreach ( $colors as $key => $val ) {
                if ( ! preg_match( '/^#[0-9a-fA-F]{3,8}$/', $val ) ) {
                    $colors[ $key ] = '#000000';
                }
            }

            echo '<style id="sgcc-custom-colors">:root{';
            echo '--sgcc-primary:' . $colors['primary'] . ';';
            echo '--sgcc-bg:' . $colors['bg'] . ';';
            echo '--sgcc-btn-bg:' . $colors['btn_bg'] . ';';
            echo '--sgcc-btn-text:' . $colors['btn_text'] . ';';
            echo '--sgcc-btn-border:' . $colors['btn_border'] . ';';
            echo '--sgcc-btn-hover-bg:' . $colors['btn_hover_bg'] . ';';
            echo '--sgcc-btn-hover-text:' . $colors['btn_hover_text'] . ';';
            echo '--sgcc-btn-hover-border:' . $colors['btn_hover_border'] . ';';
            echo '--sgcc-floating-bg:' . $colors['float_bg'] . ';';
            echo '--sgcc-floating-text:' . $colors['float_text'] . ';';
            echo '}</style>' . "\n";
        }, 5 );

        // Google Consent Mode v2.
        if ( get_option( 'sgcc_gcm_enabled', 0 ) ) {
            add_action( 'wp_head', array( $this, 'output_gcm_default' ), 1 );
        }
    }

    public function output_gcm_default() {
        ?>
        <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('consent', 'default', {
            'analytics_storage': 'denied',
            'ad_storage': 'denied',
            'ad_user_data': 'denied',
            'ad_personalization': 'denied'
        });
        </script>
        <?php
    }
}
