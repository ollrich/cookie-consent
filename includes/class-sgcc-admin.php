<?php
/**
 * Admin Settings
 *
 * @package SchonGeil_Cookie_Consent
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SGCC_Admin {

    private static $instance = null;

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'admin_menu', array( $this, 'add_menu_page' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
        add_action( 'wp_ajax_sgcc_run_scanner', array( $this, 'ajax_run_scanner' ) );
        add_action( 'wp_ajax_sgcc_export_log', array( $this, 'ajax_export_log' ) );
        add_action( 'wp_ajax_sgcc_delete_log', array( $this, 'ajax_delete_log' ) );
        add_action( 'wp_ajax_sgcc_save_cookies', array( $this, 'ajax_save_cookies' ) );

        // Flush page caches when any plugin option changes (covers options.php saves).
        add_action( 'update_option', array( $this, 'maybe_flush_cache_on_option_update' ), 10, 1 );
    }

    public function add_menu_page() {
        add_options_page(
            __( 'schongeil.de Cookie Consent', 'sgcc' ),
            __( 'Cookie Consent', 'sgcc' ),
            'manage_options',
            'sgcc-settings',
            array( $this, 'render_settings_page' )
        );
    }

    public function enqueue_admin_assets( $hook ) {
        if ( 'settings_page_sgcc-settings' !== $hook ) return;

        $suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
        wp_enqueue_style( 'sgcc-admin', SGCC_PLUGIN_URL . 'assets/css/sgcc-admin' . $suffix . '.css', array(), SGCC_VERSION );
        wp_enqueue_script( 'sgcc-admin', SGCC_PLUGIN_URL . 'assets/js/sgcc-admin' . $suffix . '.js', array(), SGCC_VERSION, true );

        wp_localize_script( 'sgcc-admin', 'sgccAdmin', array(
            'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
            'scannerNonce'  => wp_create_nonce( 'sgcc_run_scanner' ),
            'exportNonce'   => wp_create_nonce( 'sgcc_export_log' ),
            'deleteNonce'   => wp_create_nonce( 'sgcc_delete_log' ),
            'cookiesNonce'  => wp_create_nonce( 'sgcc_save_cookies' ),
            'scanningText'  => __( 'Scanning...', 'sgcc' ),
            'scanBtnText'   => __( 'Scan Website', 'sgcc' ),
            'confirmDelete' => __( 'Are you sure you want to delete all consent log entries? This cannot be undone.', 'sgcc' ),
        ) );
    }

    public function register_settings() {
        // General.
        $general_fields = array(
            'sgcc_enabled' => 'absint', 'sgcc_cookie_lifetime' => 'absint',
            'sgcc_consent_log_enabled' => 'absint', 'sgcc_consent_log_retention' => 'absint',
            'sgcc_privacy_page_id_de' => 'absint', 'sgcc_privacy_page_id_en' => 'absint',
            'sgcc_floating_icon_enabled' => 'absint', 'sgcc_floating_icon_position' => 'sanitize_text_field',
            'sgcc_floating_icon_bottom' => 'absint', 'sgcc_floating_icon_side' => 'absint',
            'sgcc_floating_icon_bg_color' => 'sanitize_hex_color', 'sgcc_floating_icon_text_color' => 'sanitize_hex_color',
            'sgcc_banner_position' => 'sanitize_text_field', 'sgcc_gcm_enabled' => 'absint',
            'sgcc_custom_link_url_de' => 'esc_url_raw', 'sgcc_custom_link_url_en' => 'esc_url_raw',
            'sgcc_custom_link_text_de' => 'sanitize_text_field', 'sgcc_custom_link_text_en' => 'sanitize_text_field',
        );
        foreach ( $general_fields as $key => $cb ) {
            register_setting( 'sgcc_general', $key, array( 'sanitize_callback' => $cb ) );
        }

        // Design.
        $design_colors = array(
            'sgcc_primary_color', 'sgcc_button_bg_color', 'sgcc_button_text_color', 'sgcc_button_border_color',
            'sgcc_button_hover_bg_color', 'sgcc_button_hover_text_color', 'sgcc_button_hover_border_color',
            'sgcc_background_color',
        );
        foreach ( $design_colors as $key ) {
            register_setting( 'sgcc_design', $key, array( 'sanitize_callback' => 'sanitize_hex_color' ) );
        }
        register_setting( 'sgcc_design', 'sgcc_texts', array( 'sanitize_callback' => array( $this, 'sanitize_texts' ) ) );
    }

    public function sanitize_texts( $input ) {
        if ( ! is_array( $input ) ) return array();
        $sanitized = array();
        foreach ( $input as $lang => $texts ) {
            $lang = sanitize_key( $lang );
            if ( is_array( $texts ) ) {
                foreach ( $texts as $key => $value ) {
                    $sanitized[ $lang ][ sanitize_key( $key ) ] = sanitize_textarea_field( $value );
                }
            }
        }
        return $sanitized;
    }

    public function render_settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) return;

        if ( get_option( 'sgcc_consent_log_enabled' ) ) {
            SGCC_Consent_Log::create_table();
        }
        $this->maybe_cleanup_logs();

        $active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'general';
        $tabs = array(
            'general'     => __( 'General', 'sgcc' ),
            'services'    => __( 'Services & Blocking', 'sgcc' ),
            'cookies'     => __( 'Cookies', 'sgcc' ),
            'design'      => __( 'Texts & Design', 'sgcc' ),
            'consent_log' => __( 'Consent Log', 'sgcc' ),
        );
        ?>
        <div class="wrap sgcc-admin">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            <nav class="nav-tab-wrapper sgcc-admin__tabs">
                <?php foreach ( $tabs as $tab_key => $tab_label ) : ?>
                    <a href="<?php echo esc_url( add_query_arg( 'tab', $tab_key, admin_url( 'options-general.php?page=sgcc-settings' ) ) ); ?>"
                       class="nav-tab <?php echo $active_tab === $tab_key ? 'nav-tab-active' : ''; ?>">
                        <?php echo esc_html( $tab_label ); ?>
                    </a>
                <?php endforeach; ?>
            </nav>
            <div class="sgcc-admin__content">
                <?php
                switch ( $active_tab ) {
                    case 'services': $this->render_services_tab(); break;
                    case 'cookies': $this->render_cookies_tab(); break;
                    case 'design': $this->render_design_tab(); break;
                    case 'consent_log': $this->render_consent_log_tab(); break;
                    default: $this->render_general_tab(); break;
                }
                ?>
            </div>
        </div>
        <?php
    }

    private function render_general_tab() {
        ?>
        <form method="post" action="options.php">
            <?php settings_fields( 'sgcc_general' ); ?>
            <table class="form-table">
                <tr>
                    <th><?php esc_html_e( 'Plugin enabled', 'sgcc' ); ?></th>
                    <td><label><input type="checkbox" name="sgcc_enabled" value="1" <?php checked( get_option( 'sgcc_enabled', 1 ) ); ?> /> <?php esc_html_e( 'Enable cookie consent and embed blocking', 'sgcc' ); ?></label></td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Cookie lifetime (days)', 'sgcc' ); ?></th>
                    <td><input type="number" name="sgcc_cookie_lifetime" value="<?php echo esc_attr( get_option( 'sgcc_cookie_lifetime', 365 ) ); ?>" min="1" max="3650" class="small-text" /></td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Privacy page (DE)', 'sgcc' ); ?></th>
                    <td>
                        <?php wp_dropdown_pages( array( 'name' => 'sgcc_privacy_page_id_de', 'selected' => get_option( 'sgcc_privacy_page_id_de', 0 ), 'show_option_none' => '— Select —', 'option_none_value' => 0 ) ); ?>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Privacy page (EN)', 'sgcc' ); ?></th>
                    <td>
                        <?php wp_dropdown_pages( array( 'name' => 'sgcc_privacy_page_id_en', 'selected' => get_option( 'sgcc_privacy_page_id_en', 0 ), 'show_option_none' => '— Select —', 'option_none_value' => 0 ) ); ?>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Banner position', 'sgcc' ); ?></th>
                    <td>
                        <select name="sgcc_banner_position">
                            <option value="bottom" <?php selected( get_option( 'sgcc_banner_position', 'bottom' ), 'bottom' ); ?>>Bottom</option>
                            <option value="top" <?php selected( get_option( 'sgcc_banner_position', 'bottom' ), 'top' ); ?>>Top</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Custom link URL (DE)', 'sgcc' ); ?></th>
                    <td>
                        <input type="url" name="sgcc_custom_link_url_de" value="<?php echo esc_attr( get_option( 'sgcc_custom_link_url_de', '' ) ); ?>" class="regular-text" />
                        <p class="description"><?php esc_html_e( 'Optional link shown in the banner, e.g. /datenschutzerklaerung/', 'sgcc' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Custom link text (DE)', 'sgcc' ); ?></th>
                    <td><input type="text" name="sgcc_custom_link_text_de" value="<?php echo esc_attr( get_option( 'sgcc_custom_link_text_de', '' ) ); ?>" class="regular-text" /></td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Custom link URL (EN)', 'sgcc' ); ?></th>
                    <td>
                        <input type="url" name="sgcc_custom_link_url_en" value="<?php echo esc_attr( get_option( 'sgcc_custom_link_url_en', '' ) ); ?>" class="regular-text" />
                        <p class="description"><?php esc_html_e( 'Optional link for English, e.g. /en/privacy-policy/', 'sgcc' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Custom link text (EN)', 'sgcc' ); ?></th>
                    <td><input type="text" name="sgcc_custom_link_text_en" value="<?php echo esc_attr( get_option( 'sgcc_custom_link_text_en', '' ) ); ?>" class="regular-text" /></td>
                </tr>

                <tr><td colspan="2"><hr /><h2><?php esc_html_e( 'Floating Icon', 'sgcc' ); ?></h2></td></tr>
                <tr>
                    <th><?php esc_html_e( 'Show floating icon', 'sgcc' ); ?></th>
                    <td><label><input type="checkbox" name="sgcc_floating_icon_enabled" value="1" <?php checked( get_option( 'sgcc_floating_icon_enabled', 1 ) ); ?> /></label></td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Position', 'sgcc' ); ?></th>
                    <td>
                        <select name="sgcc_floating_icon_position">
                            <option value="right" <?php selected( get_option( 'sgcc_floating_icon_position', 'right' ), 'right' ); ?>>Right</option>
                            <option value="left" <?php selected( get_option( 'sgcc_floating_icon_position', 'right' ), 'left' ); ?>>Left</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Bottom offset (px)', 'sgcc' ); ?></th>
                    <td><input type="number" name="sgcc_floating_icon_bottom" value="<?php echo esc_attr( get_option( 'sgcc_floating_icon_bottom', 60 ) ); ?>" min="0" max="500" class="small-text" /> px</td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Side offset (px)', 'sgcc' ); ?></th>
                    <td><input type="number" name="sgcc_floating_icon_side" value="<?php echo esc_attr( get_option( 'sgcc_floating_icon_side', 60 ) ); ?>" min="0" max="500" class="small-text" /> px</td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Icon background color', 'sgcc' ); ?></th>
                    <td><input type="color" name="sgcc_floating_icon_bg_color" value="<?php echo esc_attr( get_option( 'sgcc_floating_icon_bg_color', '#1a1a2e' ) ); ?>" /></td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Icon text color', 'sgcc' ); ?></th>
                    <td><input type="color" name="sgcc_floating_icon_text_color" value="<?php echo esc_attr( get_option( 'sgcc_floating_icon_text_color', '#ffffff' ) ); ?>" /></td>
                </tr>

                <tr><td colspan="2"><hr /><h2><?php esc_html_e( 'Consent Log', 'sgcc' ); ?></h2></td></tr>
                <tr>
                    <th><?php esc_html_e( 'Enable consent log', 'sgcc' ); ?></th>
                    <td><label><input type="checkbox" name="sgcc_consent_log_enabled" value="1" <?php checked( get_option( 'sgcc_consent_log_enabled', 0 ) ); ?> /></label></td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Log retention (months)', 'sgcc' ); ?></th>
                    <td><input type="number" name="sgcc_consent_log_retention" value="<?php echo esc_attr( get_option( 'sgcc_consent_log_retention', 12 ) ); ?>" min="1" max="120" class="small-text" /></td>
                </tr>
                <tr><td colspan="2"><hr /><h2><?php esc_html_e( 'Google Consent Mode v2', 'sgcc' ); ?></h2></td></tr>
                <tr>
                    <th><?php esc_html_e( 'Enable GCM v2', 'sgcc' ); ?></th>
                    <td>
                        <label><input type="checkbox" name="sgcc_gcm_enabled" value="1" <?php checked( get_option( 'sgcc_gcm_enabled', 0 ) ); ?> /></label>
                        <p class="description"><?php esc_html_e( 'Only if you use Google Analytics or Ads.', 'sgcc' ); ?></p>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
        <?php
    }

    private function render_services_tab() {
        $services = SGCC_Services::get_all();
        $overrides = get_option( 'sgcc_services', array() );

        if ( isset( $_POST['sgcc_save_services'] ) && check_admin_referer( 'sgcc_save_services' ) ) {
            $new_overrides = array();
            foreach ( $services as $key => $service ) {
                $new_overrides[ $key ] = array( 'enabled' => isset( $_POST['sgcc_service_enabled'][ $key ] ) );
            }
            update_option( 'sgcc_services', $new_overrides );
            $overrides = $new_overrides;
            self::flush_page_caches();
            echo '<div class="notice notice-success"><p>' . esc_html__( 'Services updated.', 'sgcc' ) . '</p></div>';
        }

        if ( isset( $_POST['sgcc_add_custom_service'] ) && check_admin_referer( 'sgcc_add_custom_service' ) ) {
            $custom = get_option( 'sgcc_custom_services', array() );
            $slug = sanitize_key( $_POST['custom_service_slug'] ?? '' );
            $name = sanitize_text_field( $_POST['custom_service_name'] ?? '' );
            $cat  = sanitize_key( $_POST['custom_service_category'] ?? 'video' );
            $patterns_raw = sanitize_textarea_field( $_POST['custom_service_patterns'] ?? '' );

            if ( $slug && $name && $patterns_raw ) {
                $patterns = array_filter( array_map( 'trim', explode( "\n", $patterns_raw ) ) );
                $custom[ $slug ] = array(
                    'name' => $name, 'category' => $cat, 'enabled' => true, 'patterns' => $patterns,
                    'icon' => 'placeholder-generic.svg',
                    'texts' => array(
                        'de' => array( 'title' => $name . '-Inhalt', 'allow' => $name . ' zulassen', 'privacy' => 'Beim Laden werden Daten an ' . $name . ' übermittelt.', 'load' => 'Inhalt laden', 'always' => $name . ' immer zulassen' ),
                        'en' => array( 'title' => $name . ' content', 'allow' => 'Allow ' . $name, 'privacy' => 'Loading transmits data to ' . $name . '.', 'load' => 'Load content', 'always' => 'Always allow ' . $name ),
                    ),
                );
                update_option( 'sgcc_custom_services', $custom );
                self::flush_page_caches();
                echo '<div class="notice notice-success"><p>' . esc_html__( 'Custom service added.', 'sgcc' ) . '</p></div>';
            }
        }

        $services = SGCC_Services::get_all();
        ?>
        <h2><?php esc_html_e( 'Configured Services', 'sgcc' ); ?></h2>
        <form method="post">
            <?php wp_nonce_field( 'sgcc_save_services' ); ?>
            <table class="widefat sgcc-admin__services-table">
                <thead><tr><th><?php esc_html_e( 'Enabled', 'sgcc' ); ?></th><th><?php esc_html_e( 'Service', 'sgcc' ); ?></th><th><?php esc_html_e( 'Category', 'sgcc' ); ?></th><th><?php esc_html_e( 'URL Patterns', 'sgcc' ); ?></th></tr></thead>
                <tbody>
                <?php foreach ( $services as $key => $service ) :
                    $is_enabled = isset( $overrides[ $key ] ) ? ! empty( $overrides[ $key ]['enabled'] ) : ! empty( $service['enabled'] );
                ?>
                    <tr>
                        <td><input type="checkbox" name="sgcc_service_enabled[<?php echo esc_attr( $key ); ?>]" value="1" <?php checked( $is_enabled ); ?> /></td>
                        <td><strong><?php echo esc_html( $service['name'] ); ?></strong></td>
                        <td><?php echo esc_html( ucfirst( $service['category'] ) ); ?></td>
                        <td><code><?php echo esc_html( implode( ', ', $service['patterns'] ) ); ?></code></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php submit_button( __( 'Save Services', 'sgcc' ), 'primary', 'sgcc_save_services' ); ?>
        </form>

        <hr />
        <h2><?php esc_html_e( 'Add Custom Service', 'sgcc' ); ?></h2>
        <form method="post">
            <?php wp_nonce_field( 'sgcc_add_custom_service' ); ?>
            <table class="form-table">
                <tr><th><label for="custom_service_slug">Slug</label></th><td><input type="text" id="custom_service_slug" name="custom_service_slug" class="regular-text" required /></td></tr>
                <tr><th><label for="custom_service_name">Name</label></th><td><input type="text" id="custom_service_name" name="custom_service_name" class="regular-text" required /></td></tr>
                <tr><th><label for="custom_service_category">Category</label></th><td>
                    <select id="custom_service_category" name="custom_service_category">
                        <option value="audio">Audio</option>
                        <option value="video">Video</option>
                    </select>
                </td></tr>
                <tr><th><label for="custom_service_patterns">URL Patterns (one per line)</label></th><td><textarea id="custom_service_patterns" name="custom_service_patterns" rows="3" class="large-text" required></textarea></td></tr>
            </table>
            <?php submit_button( __( 'Add Service', 'sgcc' ), 'secondary', 'sgcc_add_custom_service' ); ?>
        </form>

        <hr />
        <h2><?php esc_html_e( 'Embed Scanner', 'sgcc' ); ?></h2>
        <p><?php esc_html_e( 'Scan all published posts and pages for third-party embeds.', 'sgcc' ); ?></p>
        <button type="button" id="sgcc-run-scanner" class="button button-secondary"><?php esc_html_e( 'Scan Website', 'sgcc' ); ?></button>
        <div id="sgcc-scanner-results" class="sgcc-admin__scanner-results" style="display:none;"></div>
        <?php
    }

    private function render_cookies_tab() {
        $cookies = get_option( 'sgcc_cookies', array() );

        if ( isset( $_POST['sgcc_save_cookies_form'] ) && check_admin_referer( 'sgcc_save_cookies_form' ) ) {
            $new_cookies = array();
            $names = $_POST['cookie_name'] ?? array();
            $providers = $_POST['cookie_provider'] ?? array();
            $categories = $_POST['cookie_category'] ?? array();
            $desc_de = $_POST['cookie_desc_de'] ?? array();
            $desc_en = $_POST['cookie_desc_en'] ?? array();
            $durations = $_POST['cookie_duration'] ?? array();
            $types = $_POST['cookie_type'] ?? array();

            for ( $i = 0; $i < count( $names ); $i++ ) {
                $name = sanitize_text_field( $names[ $i ] ?? '' );
                if ( empty( $name ) ) continue;
                $new_cookies[] = array(
                    'name'           => $name,
                    'provider'       => sanitize_text_field( $providers[ $i ] ?? '' ),
                    'category'       => sanitize_key( $categories[ $i ] ?? 'necessary' ),
                    'description_de' => sanitize_textarea_field( $desc_de[ $i ] ?? '' ),
                    'description_en' => sanitize_textarea_field( $desc_en[ $i ] ?? '' ),
                    'duration'       => sanitize_text_field( $durations[ $i ] ?? '' ),
                    'type'           => sanitize_text_field( $types[ $i ] ?? '' ),
                );
            }
            update_option( 'sgcc_cookies', $new_cookies );
            $cookies = $new_cookies;
            self::flush_page_caches();
            echo '<div class="notice notice-success"><p>' . esc_html__( 'Cookies updated.', 'sgcc' ) . '</p></div>';
        }
        ?>
        <h2><?php esc_html_e( 'Cookie Management', 'sgcc' ); ?></h2>
        <p class="description"><?php esc_html_e( 'Manage cookies displayed in the consent popup. Assign each to a category.', 'sgcc' ); ?></p>
        <form method="post">
            <?php wp_nonce_field( 'sgcc_save_cookies_form' ); ?>
            <table class="widefat" id="sgcc-cookies-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Name', 'sgcc' ); ?></th>
                        <th><?php esc_html_e( 'Provider', 'sgcc' ); ?></th>
                        <th><?php esc_html_e( 'Category', 'sgcc' ); ?></th>
                        <th><?php esc_html_e( 'Description (DE)', 'sgcc' ); ?></th>
                        <th><?php esc_html_e( 'Description (EN)', 'sgcc' ); ?></th>
                        <th><?php esc_html_e( 'Duration', 'sgcc' ); ?></th>
                        <th><?php esc_html_e( 'Type', 'sgcc' ); ?></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $cookies as $idx => $cookie ) : ?>
                    <tr class="sgcc-cookie-row">
                        <td><input type="text" name="cookie_name[]" value="<?php echo esc_attr( $cookie['name'] ?? '' ); ?>" class="regular-text" /></td>
                        <td><input type="text" name="cookie_provider[]" value="<?php echo esc_attr( $cookie['provider'] ?? '' ); ?>" style="width:120px;" /></td>
                        <td>
                            <select name="cookie_category[]">
                                <option value="necessary" <?php selected( $cookie['category'] ?? '', 'necessary' ); ?>>Notwendig</option>
                                <option value="audio" <?php selected( $cookie['category'] ?? '', 'audio' ); ?>>Audio</option>
                                <option value="video" <?php selected( $cookie['category'] ?? '', 'video' ); ?>>Video</option>
                            </select>
                        </td>
                        <td><input type="text" name="cookie_desc_de[]" value="<?php echo esc_attr( $cookie['description_de'] ?? '' ); ?>" style="width:200px;" /></td>
                        <td><input type="text" name="cookie_desc_en[]" value="<?php echo esc_attr( $cookie['description_en'] ?? '' ); ?>" style="width:200px;" /></td>
                        <td><input type="text" name="cookie_duration[]" value="<?php echo esc_attr( $cookie['duration'] ?? '' ); ?>" style="width:80px;" /></td>
                        <td><input type="text" name="cookie_type[]" value="<?php echo esc_attr( $cookie['type'] ?? '' ); ?>" style="width:100px;" /></td>
                        <td><button type="button" class="button sgcc-remove-cookie-row">&times;</button></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p>
                <button type="button" id="sgcc-add-cookie-row" class="button button-secondary"><?php esc_html_e( '+ Add Cookie', 'sgcc' ); ?></button>
            </p>
            <?php submit_button( __( 'Save Cookies', 'sgcc' ), 'primary', 'sgcc_save_cookies_form' ); ?>
        </form>
        <?php
    }

    private function render_design_tab() {
        $texts = get_option( 'sgcc_texts', array() );
        ?>
        <form method="post" action="options.php">
            <?php settings_fields( 'sgcc_design' ); ?>
            <h2><?php esc_html_e( 'Colors', 'sgcc' ); ?></h2>
            <table class="form-table">
                <tr><th><?php esc_html_e( 'Primary color', 'sgcc' ); ?></th><td><input type="color" name="sgcc_primary_color" value="<?php echo esc_attr( get_option( 'sgcc_primary_color', '#1a1a2e' ) ); ?>" /></td></tr>
                <tr><th><?php esc_html_e( 'Background color', 'sgcc' ); ?></th><td><input type="color" name="sgcc_background_color" value="<?php echo esc_attr( get_option( 'sgcc_background_color', '#ffffff' ) ); ?>" /></td></tr>
            </table>

            <h3><?php esc_html_e( 'Button Colors', 'sgcc' ); ?></h3>
            <table class="form-table">
                <tr><th><?php esc_html_e( 'Background', 'sgcc' ); ?></th><td><input type="color" name="sgcc_button_bg_color" value="<?php echo esc_attr( get_option( 'sgcc_button_bg_color', '#16213e' ) ); ?>" /></td></tr>
                <tr><th><?php esc_html_e( 'Text', 'sgcc' ); ?></th><td><input type="color" name="sgcc_button_text_color" value="<?php echo esc_attr( get_option( 'sgcc_button_text_color', '#ffffff' ) ); ?>" /></td></tr>
                <tr><th><?php esc_html_e( 'Border', 'sgcc' ); ?></th><td><input type="color" name="sgcc_button_border_color" value="<?php echo esc_attr( get_option( 'sgcc_button_border_color', '#16213e' ) ); ?>" /></td></tr>
            </table>

            <h3><?php esc_html_e( 'Button Hover Colors', 'sgcc' ); ?></h3>
            <table class="form-table">
                <tr><th><?php esc_html_e( 'Hover Background', 'sgcc' ); ?></th><td><input type="color" name="sgcc_button_hover_bg_color" value="<?php echo esc_attr( get_option( 'sgcc_button_hover_bg_color', '#ffffff' ) ); ?>" /></td></tr>
                <tr><th><?php esc_html_e( 'Hover Text', 'sgcc' ); ?></th><td><input type="color" name="sgcc_button_hover_text_color" value="<?php echo esc_attr( get_option( 'sgcc_button_hover_text_color', '#16213e' ) ); ?>" /></td></tr>
                <tr><th><?php esc_html_e( 'Hover Border', 'sgcc' ); ?></th><td><input type="color" name="sgcc_button_hover_border_color" value="<?php echo esc_attr( get_option( 'sgcc_button_hover_border_color', '#16213e' ) ); ?>" /></td></tr>
            </table>

            <h2><?php esc_html_e( 'Custom Texts', 'sgcc' ); ?></h2>
            <p class="description"><?php esc_html_e( 'Leave empty for defaults. With Polylang, use String Translations instead.', 'sgcc' ); ?></p>
            <?php
            $text_fields = array(
                'banner_title'       => array( 'label' => 'Banner title', 'de' => 'Cookie-Einstellungen', 'en' => 'Cookie Settings' ),
                'banner_description' => array( 'label' => 'Banner text', 'de' => '', 'en' => '', 'type' => 'textarea' ),
                'btn_accept_all'     => array( 'label' => 'Accept all button', 'de' => 'Alle akzeptieren', 'en' => 'Accept all' ),
                'btn_reject'         => array( 'label' => 'Necessary only button', 'de' => 'Nur notwendige', 'en' => 'Necessary only' ),
                'btn_settings'       => array( 'label' => 'Settings button', 'de' => 'Einstellungen', 'en' => 'Settings' ),
            );
            foreach ( array( 'de', 'en' ) as $lang ) :
                $lang_label = 'de' === $lang ? 'Deutsch' : 'English';
            ?>
                <h3><?php echo esc_html( $lang_label ); ?></h3>
                <table class="form-table">
                    <?php foreach ( $text_fields as $key => $field ) :
                        $value = isset( $texts[ $lang ][ $key ] ) ? $texts[ $lang ][ $key ] : '';
                        $placeholder = $field[ $lang ] ?? '';
                    ?>
                        <tr>
                            <th><label><?php echo esc_html( $field['label'] ); ?></label></th>
                            <td>
                                <?php if ( isset( $field['type'] ) && 'textarea' === $field['type'] ) : ?>
                                    <textarea name="sgcc_texts[<?php echo esc_attr( $lang ); ?>][<?php echo esc_attr( $key ); ?>]" rows="3" class="large-text" placeholder="<?php echo esc_attr( $placeholder ); ?>"><?php echo esc_textarea( $value ); ?></textarea>
                                <?php else : ?>
                                    <input type="text" name="sgcc_texts[<?php echo esc_attr( $lang ); ?>][<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $value ); ?>" class="regular-text" placeholder="<?php echo esc_attr( $placeholder ); ?>" />
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php endforeach; ?>
            <?php submit_button(); ?>
        </form>
        <?php
    }

    private function render_consent_log_tab() {
        if ( ! get_option( 'sgcc_consent_log_enabled', 0 ) ) {
            echo '<div class="notice notice-info"><p>' . esc_html__( 'Consent logging disabled. Enable in General tab.', 'sgcc' ) . '</p></div>';
            return;
        }
        $log = SGCC_Consent_Log::get_instance();
        $page = isset( $_GET['log_page'] ) ? absint( $_GET['log_page'] ) : 1;
        $date_from = isset( $_GET['date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['date_from'] ) ) : '';
        $date_to = isset( $_GET['date_to'] ) ? sanitize_text_field( wp_unslash( $_GET['date_to'] ) ) : '';
        $result = $log->get_entries( $page, 20, $date_from, $date_to );
        ?>
        <h2><?php esc_html_e( 'Consent Log', 'sgcc' ); ?></h2>
        <div class="sgcc-admin__log-actions">
            <form method="get" style="display:inline-flex;gap:8px;align-items:center;">
                <input type="hidden" name="page" value="sgcc-settings" />
                <input type="hidden" name="tab" value="consent_log" />
                <label>From: <input type="date" name="date_from" value="<?php echo esc_attr( $date_from ); ?>" /></label>
                <label>To: <input type="date" name="date_to" value="<?php echo esc_attr( $date_to ); ?>" /></label>
                <?php submit_button( 'Filter', 'secondary', '', false ); ?>
            </form>
            <button type="button" id="sgcc-export-log" class="button button-secondary">Export CSV</button>
            <button type="button" id="sgcc-delete-log" class="button button-link-delete">Delete All</button>
        </div>
        <table class="widefat striped">
            <thead><tr><th>ID</th><th>Timestamp</th><th>IP</th><th>Consent</th><th>Version</th></tr></thead>
            <tbody>
            <?php if ( empty( $result['entries'] ) ) : ?>
                <tr><td colspan="5"><?php esc_html_e( 'No entries.', 'sgcc' ); ?></td></tr>
            <?php else : foreach ( $result['entries'] as $entry ) :
                $consent = json_decode( $entry['consent_data'], true );
                $services = isset( $consent['services'] ) ? $consent['services'] : ( isset( $consent['categories'] ) ? $consent['categories'] : array() );
            ?>
                <tr>
                    <td><?php echo esc_html( $entry['id'] ); ?></td>
                    <td><?php echo esc_html( $entry['created_at'] ); ?></td>
                    <td><code><?php echo esc_html( $entry['ip_address'] ); ?></code></td>
                    <td><?php
                        $badges = array();
                        foreach ( $services as $k => $v ) { $badges[] = esc_html( $k ) . ': ' . ( $v ? '&#10003;' : '&#10007;' ); }
                        echo implode( ' &middot; ', $badges );
                    ?></td>
                    <td><?php echo esc_html( $entry['consent_version'] ); ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
        <?php if ( $result['pages'] > 1 ) : ?>
            <div class="tablenav bottom"><div class="tablenav-pages">
                <?php for ( $i = 1; $i <= $result['pages']; $i++ ) :
                    $url = add_query_arg( array( 'page' => 'sgcc-settings', 'tab' => 'consent_log', 'date_from' => $date_from, 'date_to' => $date_to, 'log_page' => $i ), admin_url( 'options-general.php' ) );
                    if ( $i === $page ) : ?>
                        <span class="tablenav-pages-navspan button disabled"><?php echo $i; ?></span>
                    <?php else : ?>
                        <a class="button" href="<?php echo esc_url( $url ); ?>"><?php echo $i; ?></a>
                    <?php endif; endfor; ?>
            </div></div>
        <?php endif;
    }

    public function ajax_run_scanner() {
        check_ajax_referer( 'sgcc_run_scanner', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Forbidden', 403 );
        $results = SGCC_Scanner::scan();
        $summary = SGCC_Scanner::summarize( $results );
        wp_send_json_success( array( 'summary' => $summary, 'totalPosts' => count( $results ) ) );
    }

    public function ajax_export_log() {
        check_ajax_referer( 'sgcc_export_log', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Forbidden' );
        $log       = SGCC_Consent_Log::get_instance();
        $date_from = isset( $_GET['date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['date_from'] ) ) : '';
        $date_to   = isset( $_GET['date_to'] ) ? sanitize_text_field( wp_unslash( $_GET['date_to'] ) ) : '';
        $csv       = $log->export_csv( $date_from, $date_to );
        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename="sgcc-consent-log-' . gmdate( 'Y-m-d' ) . '.csv"' );
        echo $csv;
        wp_die();
    }

    public function ajax_delete_log() {
        check_ajax_referer( 'sgcc_delete_log', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Forbidden', 403 );
        $log = SGCC_Consent_Log::get_instance();
        $log->delete_all();
        wp_send_json_success();
    }

    public function ajax_save_cookies() {
        check_ajax_referer( 'sgcc_save_cookies', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Forbidden', 403 );
        // Handled via form POST, this is a fallback.
        wp_send_json_success();
    }

    private function maybe_cleanup_logs() {
        if ( ! get_option( 'sgcc_consent_log_enabled', 0 ) ) return;
        $last_cleanup = get_transient( 'sgcc_last_log_cleanup' );
        if ( $last_cleanup ) return;
        $retention = absint( get_option( 'sgcc_consent_log_retention', 12 ) );
        $log = SGCC_Consent_Log::get_instance();
        $log->delete_old( $retention );
        set_transient( 'sgcc_last_log_cleanup', time(), DAY_IN_SECONDS );
    }

    /* ==================================================================
       Cache Management
       ================================================================== */

    /**
     * Fired by update_option hook – flush caches when any sgcc_ option changes.
     */
    public function maybe_flush_cache_on_option_update( $option ) {
        if ( 0 === strpos( $option, 'sgcc_' ) ) {
            self::flush_page_caches();
        }
    }

    /**
     * Flush all known page caches and update the config hash.
     *
     * Called after any settings change so that cached pages get regenerated
     * with the new inline config (sgccConfig) and updated placeholder HTML.
     */
    public static function flush_page_caches() {
        // Prevent multiple flushes in the same request.
        static $flushed = false;
        if ( $flushed ) {
            return;
        }
        $flushed = true;

        // Update config hash so frontend can detect stale caches.
        self::update_config_hash();

        // WordPress object cache.
        wp_cache_flush();

        // WP Super Cache.
        if ( function_exists( 'wp_cache_clear_cache' ) ) {
            wp_cache_clear_cache();
        }

        // W3 Total Cache.
        if ( function_exists( 'w3tc_flush_all' ) ) {
            w3tc_flush_all();
        }

        // WP Rocket.
        if ( function_exists( 'rocket_clean_domain' ) ) {
            rocket_clean_domain();
        }

        // LiteSpeed Cache.
        if ( class_exists( 'LiteSpeed_Cache_API' ) && method_exists( 'LiteSpeed_Cache_API', 'purge_all' ) ) {
            LiteSpeed_Cache_API::purge_all();
        }
        // LiteSpeed Cache v4+.
        if ( class_exists( '\LiteSpeed\Purge' ) && method_exists( '\LiteSpeed\Purge', 'purge_all' ) ) {
            \LiteSpeed\Purge::purge_all();
        }

        // Autoptimize.
        if ( class_exists( 'autoptimizeCache' ) && method_exists( 'autoptimizeCache', 'clearall' ) ) {
            autoptimizeCache::clearall();
        }

        // WP Fastest Cache – true = also clear minified CSS/JS.
        if ( function_exists( 'wpfc_clear_all_cache' ) ) {
            wpfc_clear_all_cache( true );
        }

        // Hummingbird.
        if ( class_exists( '\Jeremypercy\Minification\Cache' ) || has_action( 'wphb_clear_page_cache' ) ) {
            do_action( 'wphb_clear_page_cache' );
        }

        // SG Optimizer (SiteGround).
        if ( function_exists( 'sg_cachepress_purge_cache' ) ) {
            sg_cachepress_purge_cache();
        }

        // Kinsta Cache – triggered via their MU plugin action.
        if ( has_action( 'kinsta_clear_cache_all' ) ) {
            do_action( 'kinsta_clear_cache_all' );
        }

        // Comet Cache / ZenCache.
        if ( class_exists( 'comet_cache' ) && method_exists( 'comet_cache', 'clear' ) ) {
            comet_cache::clear();
        }

        // Generic cache-clear action used by some plugins.
        do_action( 'sgcc_cache_flushed' );
    }

    /**
     * Store a hash of the current config so the frontend can detect stale caches.
     */
    private static function update_config_hash() {
        $hash_data = array(
            'version'     => SGCC_VERSION,
            'services'    => get_option( 'sgcc_services', array() ),
            'custom'      => get_option( 'sgcc_custom_services', array() ),
            'cookies'     => get_option( 'sgcc_cookies', array() ),
            'texts'       => get_option( 'sgcc_texts', array() ),
            'colors'      => array(
                get_option( 'sgcc_primary_color', '' ),
                get_option( 'sgcc_button_bg_color', '' ),
                get_option( 'sgcc_button_text_color', '' ),
            ),
            'floating'    => get_option( 'sgcc_floating_icon_enabled', 1 ),
            'banner_pos'  => get_option( 'sgcc_banner_position', 'bottom' ),
        );
        update_option( 'sgcc_config_hash', md5( wp_json_encode( $hash_data ) ), true );
    }
}
