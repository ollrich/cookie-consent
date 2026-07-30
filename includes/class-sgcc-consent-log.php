<?php
/**
 * Consent Log
 *
 * Handles consent logging to custom database table.
 *
 * @package SchonGeil_Cookie_Consent
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SGCC_Consent_Log {

    /**
     * Singleton instance.
     *
     * @var SGCC_Consent_Log|null
     */
    private static $instance = null;

    /**
     * Get singleton instance.
     *
     * @return SGCC_Consent_Log
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
        add_action( 'wp_ajax_sgcc_log_consent', array( $this, 'ajax_log_consent' ) );
        add_action( 'wp_ajax_nopriv_sgcc_log_consent', array( $this, 'ajax_log_consent' ) );
        add_action( 'sgcc_daily_log_cleanup', array( $this, 'run_scheduled_cleanup' ) );
    }

    /**
     * Daily WP-Cron task: prune entries past the retention period.
     */
    public function run_scheduled_cleanup() {
        if ( ! get_option( 'sgcc_consent_log_enabled', 0 ) ) {
            return;
        }
        $this->delete_old( absint( get_option( 'sgcc_consent_log_retention', 12 ) ) );
    }

    /**
     * Get the table name.
     *
     * @return string
     */
    public static function get_table_name() {
        global $wpdb;
        return $wpdb->prefix . 'sgcc_consent_log';
    }

    /**
     * Create the consent log database table.
     */
    public static function create_table() {
        global $wpdb;

        $table_name      = self::get_table_name();
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table_name} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            ip_address varchar(45) NOT NULL DEFAULT '',
            user_agent_hash varchar(64) NOT NULL DEFAULT '',
            consent_data text NOT NULL,
            consent_version varchar(20) NOT NULL DEFAULT '',
            PRIMARY KEY  (id),
            KEY created_at (created_at)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    /**
     * AJAX handler to log consent.
     */
    public function ajax_log_consent() {
        // No nonce verification – intentional.
        // Consent logging is a public, non-destructive action called via sendBeacon
        // from cached pages. WordPress nonces expire after 12-24 h but are embedded
        // in the cached HTML, making them permanently stale on sites with full-page
        // caching (WP Rocket, LiteSpeed, etc.). We use an origin check instead.
        if ( ! $this->verify_request_origin() ) {
            wp_send_json_error( 'Invalid origin', 403 );
        }

        if ( ! get_option( 'sgcc_consent_log_enabled', 0 ) ) {
            wp_send_json_error( 'Logging disabled' );
        }

        $consent_json = isset( $_POST['consent'] ) ? sanitize_text_field( wp_unslash( $_POST['consent'] ) ) : '';
        if ( empty( $consent_json ) ) {
            wp_send_json_error( 'No consent data' );
        }

        $consent_data = json_decode( $consent_json, true );
        if ( ! is_array( $consent_data ) ) {
            wp_send_json_error( 'Invalid consent data' );
        }

        $this->log_entry( $consent_data );

        wp_send_json_success();
    }

    /**
     * Verify that the request originates from the same site.
     *
     * Checks HTTP Origin and Referer headers against the site URL.
     * This replaces nonce verification for this public, non-destructive endpoint
     * because nonces embedded in cached pages expire and break logging.
     *
     * @return bool True if the request origin matches the site.
     */
    private function verify_request_origin() {
        $site_host = wp_parse_url( home_url(), PHP_URL_HOST );
        if ( empty( $site_host ) ) {
            return false;
        }

        // Check Origin header first (always sent by sendBeacon and modern browsers).
        if ( ! empty( $_SERVER['HTTP_ORIGIN'] ) ) {
            $origin_host = wp_parse_url( sanitize_url( wp_unslash( $_SERVER['HTTP_ORIGIN'] ) ), PHP_URL_HOST );
            return $origin_host === $site_host;
        }

        // Fall back to Referer header.
        if ( ! empty( $_SERVER['HTTP_REFERER'] ) ) {
            $referer_host = wp_parse_url( sanitize_url( wp_unslash( $_SERVER['HTTP_REFERER'] ) ), PHP_URL_HOST );
            return $referer_host === $site_host;
        }

        // No origin info available – reject to prevent abuse.
        return false;
    }

    /**
     * Log a consent entry.
     *
     * @param array $consent_data Consent data.
     * @return int|false Insert ID or false.
     */
    public function log_entry( $consent_data ) {
        global $wpdb;

        $table_name = self::get_table_name();

        // Anonymize IP: set last octet to 0.
        $ip = $this->anonymize_ip( $this->get_client_ip() );

        // Hash user agent.
        $user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
        $ua_hash    = hash( 'sha256', $user_agent );

        // Restrict to a plain version string – the value is client-supplied and
        // ends up in CSV exports, so strip anything that could act as a formula.
        $version = isset( $consent_data['version'] ) ? sanitize_text_field( $consent_data['version'] ) : SGCC_VERSION;
        $version = substr( preg_replace( '/[^0-9A-Za-z._-]/', '', $version ), 0, 20 );
        if ( '' === $version ) {
            $version = SGCC_VERSION;
        }

        $result = $wpdb->insert(
            $table_name,
            array(
                'created_at'      => current_time( 'mysql' ),
                'ip_address'      => $ip,
                'user_agent_hash' => $ua_hash,
                'consent_data'    => wp_json_encode( $consent_data ),
                'consent_version' => $version,
            ),
            array( '%s', '%s', '%s', '%s', '%s' )
        );

        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Get log entries with pagination.
     *
     * @param int    $page     Page number.
     * @param int    $per_page Items per page.
     * @param string $date_from Date from (Y-m-d).
     * @param string $date_to   Date to (Y-m-d).
     * @return array
     */
    public function get_entries( $page = 1, $per_page = 20, $date_from = '', $date_to = '' ) {
        global $wpdb;

        $table_name = self::get_table_name();
        $offset     = ( $page - 1 ) * $per_page;
        $where      = array();
        $values     = array();

        if ( ! empty( $date_from ) ) {
            $where[]  = 'created_at >= %s';
            $values[] = $date_from . ' 00:00:00';
        }
        if ( ! empty( $date_to ) ) {
            $where[]  = 'created_at <= %s';
            $values[] = $date_to . ' 23:59:59';
        }

        $where_sql = '';
        if ( ! empty( $where ) ) {
            $where_sql = 'WHERE ' . implode( ' AND ', $where );
        }

        // Get total count.
        if ( ! empty( $values ) ) {
            $total = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table_name} {$where_sql}", ...$values ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL
        } else {
            $total = $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL
        }

        // Get entries.
        $query_values   = array_merge( $values, array( $per_page, $offset ) );
        $order_limit    = 'ORDER BY created_at DESC LIMIT %d OFFSET %d';

        if ( ! empty( $values ) ) {
            $entries = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table_name} {$where_sql} {$order_limit}", ...$query_values ), ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL
        } else {
            $entries = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table_name} {$order_limit}", $per_page, $offset ), ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL
        }

        return array(
            'entries' => $entries ?: array(),
            'total'   => (int) $total,
            'pages'   => ceil( (int) $total / $per_page ),
        );
    }

    /**
     * Delete all log entries.
     *
     * @return int|false Number of rows deleted or false.
     */
    public function delete_all() {
        global $wpdb;
        $table_name = self::get_table_name();
        return $wpdb->query( "TRUNCATE TABLE {$table_name}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL
    }

    /**
     * Delete old log entries.
     *
     * @param int $months Months to retain.
     * @return int|false Number of rows deleted or false.
     */
    public function delete_old( $months = 12 ) {
        global $wpdb;
        $table_name = self::get_table_name();
        $months     = absint( $months );
        if ( $months < 1 ) {
            $months = 12;
        }
        // created_at is stored via current_time( 'mysql' ) (site-local time),
        // so the cutoff must be computed in the same timezone.
        $cutoff = gmdate( 'Y-m-d H:i:s', strtotime( '-' . $months . ' months', current_time( 'timestamp' ) ) );

        return $wpdb->query( $wpdb->prepare( "DELETE FROM {$table_name} WHERE created_at < %s", $cutoff ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    }

    /**
     * Export entries as CSV.
     *
     * @param string $date_from Date from.
     * @param string $date_to   Date to.
     * @return string CSV content.
     */
    public function export_csv( $date_from = '', $date_to = '' ) {
        $result  = $this->get_entries( 1, 999999, $date_from, $date_to );
        $entries = $result['entries'];

        $output = fopen( 'php://temp', 'r+' );

        // Header row.
        fputcsv( $output, array( 'ID', 'Timestamp', 'IP (anonymized)', 'User Agent Hash', 'Consent Data', 'Version' ) );

        foreach ( $entries as $entry ) {
            fputcsv( $output, array(
                $entry['id'],
                $entry['created_at'],
                $entry['ip_address'],
                $entry['user_agent_hash'],
                $entry['consent_data'],
                $entry['consent_version'],
            ) );
        }

        rewind( $output );
        $csv = stream_get_contents( $output );
        fclose( $output );

        return $csv;
    }

    /**
     * Anonymize an IP address by truncation.
     *
     * IPv4: sets the last octet to 0 (e.g. 192.168.1.42 → 192.168.1.0).
     * IPv6: masks the last 80 bits (5 groups replaced with :0).
     *
     * This method is GDPR/DSGVO-compliant and used by Google Analytics.
     *
     * @param string $ip IP address.
     * @return string Anonymized IP.
     */
    private function anonymize_ip( $ip ) {
        if ( empty( $ip ) ) {
            return '0.0.0.0';
        }

        // IPv4.
        if ( false !== strpos( $ip, '.' ) && false === strpos( $ip, ':' ) ) {
            $parts = explode( '.', $ip );
            if ( 4 !== count( $parts ) ) {
                return '0.0.0.0';
            }
            $parts[3] = '0';
            return implode( '.', $parts );
        }

        // IPv6: mask the last 80 bits (keep the /48 prefix). Binary masking
        // via inet_pton also covers compressed notations like 2001:db8::1.
        if ( false !== strpos( $ip, ':' ) ) {
            $packed = inet_pton( $ip );
            if ( false === $packed || 16 !== strlen( $packed ) ) {
                return '0.0.0.0';
            }
            return inet_ntop( substr( $packed, 0, 6 ) . str_repeat( "\0", 10 ) );
        }

        return '0.0.0.0';
    }

    /**
     * Get client IP address.
     *
     * @return string
     */
    private function get_client_ip() {
        $ip = '';

        // Use REMOTE_ADDR as the primary source – it cannot be spoofed.
        // HTTP_X_FORWARDED_FOR is only trusted behind a known reverse proxy
        // and can be manipulated by the client in other setups.
        if ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
            $ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
        }

        return filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '';
    }
}
