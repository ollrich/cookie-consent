<?php
/**
 * Plugin Name: schongeil.de Cookie Consent
 * Plugin URI: https://schongeil.de
 * Description: Schlankes, selbst gehostetes Cookie Consent Plugin für schongeil.de. Blockiert Drittanbieter-Embeds (YouTube, Vimeo, SoundCloud, Bandcamp, hearthis.at, Instagram, Spotify, Mixcloud) vor der Einwilligung mit dienstspezifischen Platzhaltern.
 * Version: 1.7
 * Author: schongeil.de
 * Author URI: https://schongeil.de
 * License: GPL-2.0+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: sgcc
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'SGCC_VERSION', '1.7' );
define( 'SGCC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SGCC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'SGCC_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'SGCC_CONSENT_COOKIE', 'sgcc_consent' );
define( 'SGCC_DB_VERSION', '1.1' );

require_once SGCC_PLUGIN_DIR . 'includes/trait-sgcc-l10n.php';
require_once SGCC_PLUGIN_DIR . 'includes/class-sgcc-services.php';
require_once SGCC_PLUGIN_DIR . 'includes/class-sgcc-blocker.php';
require_once SGCC_PLUGIN_DIR . 'includes/class-sgcc-frontend.php';
require_once SGCC_PLUGIN_DIR . 'includes/class-sgcc-consent-log.php';
require_once SGCC_PLUGIN_DIR . 'includes/class-sgcc-scanner.php';
require_once SGCC_PLUGIN_DIR . 'includes/class-sgcc-polylang.php';
require_once SGCC_PLUGIN_DIR . 'includes/class-sgcc-admin.php';
require_once SGCC_PLUGIN_DIR . 'includes/class-sgcc-core.php';

/**
 * Plugin activation.
 */
function sgcc_activate() {
    $defaults = array(
        'sgcc_enabled'                => 1,
        'sgcc_cookie_lifetime'        => 365,
        'sgcc_consent_log_enabled'    => 0,
        'sgcc_consent_log_retention'  => 12,
        'sgcc_privacy_page_id_de'     => 0,
        'sgcc_privacy_page_id_en'     => 0,
        'sgcc_floating_icon_enabled'  => 1,
        'sgcc_floating_icon_position' => 'right',
        'sgcc_floating_icon_bottom'   => 20,
        'sgcc_floating_icon_bg_color' => '#1a1a2e',
        'sgcc_floating_icon_text_color' => '#ffffff',
        'sgcc_banner_position'        => 'bottom',
        'sgcc_primary_color'          => '#1a1a2e',
        'sgcc_button_bg_color'        => '#16213e',
        'sgcc_button_text_color'      => '#ffffff',
        'sgcc_button_border_color'    => '#16213e',
        'sgcc_button_hover_bg_color'  => '#ffffff',
        'sgcc_button_hover_text_color'=> '#16213e',
        'sgcc_button_hover_border_color' => '#16213e',
        'sgcc_background_color'       => '#ffffff',
        'sgcc_services'               => array(),
        'sgcc_custom_services'        => array(),
        'sgcc_cookies'                => array(),
        'sgcc_texts'                  => array(),
        'sgcc_custom_link_url_de'     => '',
        'sgcc_custom_link_url_en'     => '',
        'sgcc_custom_link_text_de'    => '',
        'sgcc_custom_link_text_en'    => '',
        'sgcc_floating_icon_side'     => 20,
        'sgcc_db_version'             => SGCC_DB_VERSION,
        'sgcc_gcm_enabled'            => 0,
    );

    foreach ( $defaults as $key => $value ) {
        if ( false === get_option( $key ) ) {
            add_option( $key, $value );
        }
    }

    // Set default necessary cookies.
    $cookies = get_option( 'sgcc_cookies', array() );
    if ( empty( $cookies ) ) {
        update_option( 'sgcc_cookies', array(
            array(
                'name'        => 'pll_language',
                'provider'    => 'Polylang',
                'category'    => 'necessary',
                'description_de' => 'Speichert die gewählte Sprache.',
                'description_en' => 'Stores the selected language.',
                'duration'    => '1 Jahr',
                'type'        => 'First Party',
            ),
            array(
                'name'        => 'sgcc_consent',
                'provider'    => 'schongeil.de',
                'category'    => 'necessary',
                'description_de' => 'Speichert die Cookie-Einwilligung.',
                'description_en' => 'Stores the cookie consent choice.',
                'duration'    => '1 Jahr',
                'type'        => 'First Party',
            ),
        ) );
    }

    if ( get_option( 'sgcc_consent_log_enabled' ) ) {
        SGCC_Consent_Log::create_table();
    }

    if ( ! wp_next_scheduled( 'sgcc_daily_log_cleanup' ) ) {
        wp_schedule_event( time(), 'daily', 'sgcc_daily_log_cleanup' );
    }

    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'sgcc_activate' );

/**
 * Plugin deactivation.
 */
function sgcc_deactivate() {
    wp_clear_scheduled_hook( 'sgcc_daily_log_cleanup' );
    flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'sgcc_deactivate' );

/**
 * Add settings link to plugins page.
 *
 * @param array $links Plugin action links.
 * @return array Modified links.
 */
function sgcc_plugin_action_links( $links ) {
    $settings_link = '<a href="' . esc_url( admin_url( 'options-general.php?page=sgcc-settings' ) ) . '">' . esc_html__( 'Settings', 'sgcc' ) . '</a>';
    array_unshift( $links, $settings_link );
    return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'sgcc_plugin_action_links' );

/**
 * Initialize the plugin.
 */
function sgcc_init() {
    SGCC_Core::get_instance();
}
add_action( 'plugins_loaded', 'sgcc_init' );
