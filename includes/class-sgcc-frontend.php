<?php
/**
 * Frontend
 *
 * Handles banner rendering, settings popup, and floating icon.
 *
 * @package SchonGeil_Cookie_Consent
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SGCC_Frontend {

    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        if ( ! get_option( 'sgcc_enabled', 1 ) ) {
            return;
        }
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_action( 'wp_footer', array( $this, 'render_banner' ), 100 );
        add_action( 'wp_footer', array( $this, 'render_settings_popup' ), 101 );
        add_action( 'wp_footer', array( $this, 'render_floating_icon' ), 102 );
        add_shortcode( 'sgcc_settings', array( $this, 'render_settings_shortcode' ) );
    }

    public function enqueue_assets() {
        $suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

        wp_enqueue_style( 'sgcc-frontend', SGCC_PLUGIN_URL . 'assets/css/sgcc-frontend' . $suffix . '.css', array(), SGCC_VERSION );
        wp_enqueue_script( 'sgcc-frontend', SGCC_PLUGIN_URL . 'assets/js/sgcc-frontend' . $suffix . '.js', array(), SGCC_VERSION, true );

        $config = array(
            'cookieName'     => SGCC_CONSENT_COOKIE,
            'cookieLifetime' => absint( get_option( 'sgcc_cookie_lifetime', 365 ) ),
            'cookiePath'     => '/',
            'cookieSecure'   => is_ssl(),
            'consentVersion' => SGCC_VERSION,
            'isPrivacyPage'  => $this->is_privacy_page(),
            'logEnabled'     => (bool) get_option( 'sgcc_consent_log_enabled', 0 ),
            'logNonce'       => wp_create_nonce( 'sgcc_log_consent' ),
            'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
            'services'       => $this->get_services_for_js(),
            'categories'     => $this->get_categories_for_js(),
            'cookies'        => get_option( 'sgcc_cookies', array() ),
            'buttonColors'   => array(
                'bgColor'        => get_option( 'sgcc_button_bg_color', '#1a1a2e' ),
                'textColor'      => get_option( 'sgcc_button_text_color', '#ffffff' ),
                'borderColor'    => get_option( 'sgcc_button_border_color', '#1a1a2e' ),
                'hoverBgColor'   => get_option( 'sgcc_button_hover_bg_color', '#0f3460' ),
                'hoverTextColor' => get_option( 'sgcc_button_hover_text_color', '#ffffff' ),
                'hoverBorderColor' => get_option( 'sgcc_button_hover_border_color', '#0f3460' ),
            ),
            'reloadOnConsent'=> true,
            'configHash'     => get_option( 'sgcc_config_hash', '' ),
        );

        wp_add_inline_script( 'sgcc-frontend', 'var sgccConfig = ' . wp_json_encode( $config ) . ';', 'before' );
    }

    private function is_privacy_page() {
        if ( ! is_page() ) {
            return false;
        }
        $current_page_id = get_queried_object_id();

        // Check both language privacy pages.
        foreach ( array( 'de', 'en' ) as $lang ) {
            $page_id = absint( get_option( 'sgcc_privacy_page_id_' . $lang, 0 ) );
            if ( $page_id && $current_page_id === $page_id ) {
                return true;
            }
            // Check Polylang translations.
            if ( $page_id && function_exists( 'pll_get_post_translations' ) ) {
                $translations = pll_get_post_translations( $page_id );
                if ( in_array( $current_page_id, $translations, true ) ) {
                    return true;
                }
            }
        }

        // Fallback to WP privacy page.
        $wp_privacy = absint( get_option( 'wp_page_for_privacy_policy', 0 ) );
        if ( $wp_privacy && $current_page_id === $wp_privacy ) {
            return true;
        }

        return false;
    }

    private function get_services_for_js() {
        $services = SGCC_Services::get_enabled();
        $js_data  = array();
        foreach ( $services as $key => $service ) {
            $js_data[ $key ] = array(
                'name'     => $service['name'],
                'category' => $service['category'],
                'patterns' => $service['patterns'],
            );
        }
        return $js_data;
    }

    private function get_categories_for_js() {
        $categories = SGCC_Services::get_default_categories();
        $lang = $this->get_current_lang();
        $js_data = array();
        foreach ( $categories as $key => $cat ) {
            $js_data[ $key ] = array(
                'name'     => $cat[ 'name_' . $lang ] ?? $cat['name_de'],
                'required' => ! empty( $cat['required'] ),
            );
        }
        return $js_data;
    }

    public function render_banner() {
        $position = get_option( 'sgcc_banner_position', 'bottom' );
        $lang     = $this->get_current_lang();
        $privacy_url = $this->get_privacy_url();
        $custom_link_url  = get_option( 'sgcc_custom_link_url_' . $lang, '' );
        if ( empty( $custom_link_url ) ) {
            // Fallback to other language.
            $fallback_lang    = ( 'en' === $lang ) ? 'de' : 'en';
            $custom_link_url  = get_option( 'sgcc_custom_link_url_' . $fallback_lang, '' );
        }
        $custom_link_text = get_option( 'sgcc_custom_link_text_' . $lang, '' );

        $texts = array(
            'title'       => $this->get_text( 'banner_title', 'Cookie-Einstellungen', 'Cookie Settings' ),
            'description' => $this->get_text(
                'banner_description',
                'Diese Website nutzt eingebettete Inhalte von Drittanbietern (z.B. YouTube, SoundCloud). Beim Laden dieser Inhalte werden Daten an die jeweiligen Anbieter übermittelt.',
                'This website uses embedded content from third-party providers (e.g. YouTube, SoundCloud). Loading this content transmits data to the respective providers.'
            ),
            'accept_all'  => $this->get_text( 'btn_accept_all', 'Alle akzeptieren', 'Accept all' ),
            'reject'      => $this->get_text( 'btn_reject', 'Nur notwendige', 'Necessary only' ),
            'settings'    => $this->get_text( 'btn_settings', 'Einstellungen', 'Settings' ),
            'privacy_link'=> $this->get_text( 'privacy_link', 'Datenverarbeitungserklärung', 'Privacy policy' ),
        );

        include SGCC_PLUGIN_DIR . 'templates/banner.php';
    }

    public function render_settings_popup() {
        $lang        = $this->get_current_lang();
        $services    = SGCC_Services::get_enabled();
        $categories  = SGCC_Services::get_default_categories();
        $cookies     = get_option( 'sgcc_cookies', array() );
        $privacy_url = $this->get_privacy_url();

        $texts = array(
            'title'         => $this->get_text( 'popup_title', 'Cookie-Einstellungen', 'Cookie Settings' ),
            'description'   => $this->get_text(
                'popup_description',
                'Hier kannst du einstellen, welche Arten von Cookies und eingebetteten Inhalten du zulassen möchtest.',
                'Here you can choose which types of cookies and embedded content you want to allow.'
            ),
            'always_active' => $this->get_text( 'always_active', 'Immer aktiv', 'Always active' ),
            'save'          => $this->get_text( 'btn_save', 'Auswahl speichern', 'Save selection' ),
            'accept_all'    => $this->get_text( 'btn_accept_all', 'Alle akzeptieren', 'Accept all' ),
            'privacy_link'  => $this->get_text( 'privacy_link', 'Datenverarbeitungserklärung', 'Privacy policy' ),
        );

        include SGCC_PLUGIN_DIR . 'templates/settings-popup.php';
    }

    public function render_floating_icon() {
        if ( ! get_option( 'sgcc_floating_icon_enabled', 1 ) ) {
            return;
        }

        $position   = get_option( 'sgcc_floating_icon_position', 'left' );
        $bottom     = absint( get_option( 'sgcc_floating_icon_bottom', 60 ) );
        $side       = absint( get_option( 'sgcc_floating_icon_side', 60 ) );
        $bg_color   = get_option( 'sgcc_floating_icon_bg_color', '#1a1a2e' );
        $text_color = get_option( 'sgcc_floating_icon_text_color', '#ffffff' );
        $label      = $this->get_text( 'floating_label', 'Cookie-Einstellungen ändern', 'Change cookie settings' );
        $tooltip    = $this->get_text( 'floating_tooltip', 'Klicke hier, um deine Cookie-Einstellungen für eingebettete Inhalte wie Videos und Musik-Player anzupassen.', 'Click here to adjust your cookie settings for embedded content like videos and music players.' );

        $side_css = ( 'left' === $position ) ? 'left:' . $side . 'px' : 'right:' . $side . 'px';
        ?>
        <button class="sgcc-floating-icon"
                type="button"
                data-sgcc-action="open-settings"
                aria-label="<?php echo esc_attr( $label ); ?>"
                title="<?php echo esc_attr( $tooltip ); ?>"
                style="display:none;bottom:<?php echo esc_attr( $bottom ); ?>px;<?php echo esc_attr( $side_css ); ?>;background:<?php echo esc_attr( $bg_color ); ?>;color:<?php echo esc_attr( $text_color ); ?>;">
            <svg viewBox="0 0 24 24" width="26" height="26" fill="currentColor" aria-hidden="true">
                <path d="M21.6 11.2c-.1 0-.2 0-.3-.1a2.5 2.5 0 0 1-1.7-3.2c.1-.3 0-.5-.3-.6a10.1 10.1 0 0 0-3.3-1.4c-.3-.1-.5.1-.6.3a2.5 2.5 0 0 1-3.4 1.2 2.5 2.5 0 0 1-1.2-1.5c-.1-.3-.3-.4-.6-.4A10 10 0 1 0 22 12.1c0-.3-.2-.6-.4-.6zM7.5 10a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3zm2 7a1.25 1.25 0 1 1 0-2.5 1.25 1.25 0 0 1 0 2.5zm2.5-5a1 1 0 1 1 0-2 1 1 0 0 1 0 2zm4 4.5a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3z"/>
            </svg>
        </button>
        <?php
    }

    /**
     * Shortcode [sgcc_settings] – renders a link that opens the cookie settings popup.
     *
     * Attributes:
     *   text  – Custom link text (default: auto-detect by language).
     *   class – Additional CSS class(es) for the link.
     *   tag   – HTML tag: 'a' (default) or 'button'.
     *
     * Usage:
     *   [sgcc_settings]
     *   [sgcc_settings text="Cookie-Einstellungen ändern"]
     *   [sgcc_settings text="Manage cookies" class="footer-link" tag="button"]
     */
    public function render_settings_shortcode( $atts ) {
        $atts = shortcode_atts( array(
            'text'  => '',
            'class' => '',
            'tag'   => 'a',
        ), $atts, 'sgcc_settings' );

        $lang = $this->get_current_lang();
        $text = ! empty( $atts['text'] )
            ? $atts['text']
            : ( 'en' === $lang ? 'Cookie Settings' : 'Cookie-Einstellungen' );

        $extra_class = ! empty( $atts['class'] ) ? ' ' . esc_attr( $atts['class'] ) : '';
        $tag = ( 'button' === $atts['tag'] ) ? 'button' : 'a';

        if ( 'a' === $tag ) {
            return '<a href="#" class="sgcc-settings-link' . $extra_class . '" data-sgcc-action="open-settings" role="button">' . esc_html( $text ) . '</a>';
        }

        return '<button type="button" class="sgcc-settings-link' . $extra_class . '" data-sgcc-action="open-settings">' . esc_html( $text ) . '</button>';
    }

    private function get_privacy_url() {
        $lang = $this->get_current_lang();
        $page_id = absint( get_option( 'sgcc_privacy_page_id_' . $lang, 0 ) );
        if ( ! $page_id ) {
            $fallback = ( 'en' === $lang ) ? 'de' : 'en';
            $page_id = absint( get_option( 'sgcc_privacy_page_id_' . $fallback, 0 ) );
        }
        if ( ! $page_id ) {
            $page_id = absint( get_option( 'wp_page_for_privacy_policy', 0 ) );
        }
        return $page_id ? get_permalink( $page_id ) : '';
    }

    private function get_current_lang() {
        if ( function_exists( 'pll_current_language' ) ) {
            $lang = pll_current_language( 'slug' );
            return ( 'en' === $lang ) ? 'en' : 'de';
        }
        return 'de';
    }

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
        if ( isset( $custom_texts[ $lang ][ $key ] ) && ! empty( $custom_texts[ $lang ][ $key ] ) ) {
            return $custom_texts[ $lang ][ $key ];
        }
        return ( 'en' === $lang ) ? $default_en : $default_de;
    }
}
