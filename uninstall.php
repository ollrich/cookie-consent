<?php
/**
 * schongeil.de Cookie Consent Uninstall
 *
 * Removes all plugin data on uninstall.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Remove all options.
$options = array(
    'sgcc_enabled',
    'sgcc_cookie_lifetime',
    'sgcc_consent_log_enabled',
    'sgcc_consent_log_retention',
    'sgcc_privacy_page_id_de',
    'sgcc_privacy_page_id_en',
    'sgcc_floating_icon_enabled',
    'sgcc_floating_icon_position',
    'sgcc_floating_icon_bottom',
    'sgcc_floating_icon_bg_color',
    'sgcc_floating_icon_text_color',
    'sgcc_banner_position',
    'sgcc_primary_color',
    'sgcc_background_color',
    'sgcc_button_bg_color',
    'sgcc_button_text_color',
    'sgcc_button_border_color',
    'sgcc_button_hover_bg_color',
    'sgcc_button_hover_text_color',
    'sgcc_button_hover_border_color',
    'sgcc_services',
    'sgcc_custom_services',
    'sgcc_cookies',
    'sgcc_texts',
    'sgcc_db_version',
    'sgcc_gcm_enabled',
    'sgcc_custom_link_url_de',
    'sgcc_custom_link_url_en',
    'sgcc_custom_link_text_de',
    'sgcc_custom_link_text_en',
    'sgcc_floating_icon_side',
    'sgcc_categories',
    'sgcc_config_hash',
);

foreach ( $options as $option ) {
    delete_option( $option );
}

// Remove transients.
delete_transient( 'sgcc_last_log_cleanup' );

// Drop consent log table.
global $wpdb;
$table_name = $wpdb->prefix . 'sgcc_consent_log';
$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL
