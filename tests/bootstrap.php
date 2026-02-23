<?php
/**
 * PHPUnit Bootstrap – minimale WordPress-Stubs.
 *
 * Definiert alle WordPress-Funktionen, die von den Plugin-Klassen beim Laden
 * aufgerufen werden, damit die Tests ohne vollstaendige WP-Installation laufen.
 *
 * @package SchonGeil_Cookie_Consent\Tests
 */

// WordPress-Guard.
define( 'ABSPATH', '/tmp/wordpress/' );

// Plugin-Konstanten.
define( 'SGCC_VERSION', '1.6' );
define( 'SGCC_PLUGIN_DIR', dirname( __DIR__ ) . '/' );
define( 'SGCC_PLUGIN_URL', 'https://example.com/wp-content/plugins/schongeil-cookie-consent/' );
define( 'SGCC_PLUGIN_BASENAME', 'schongeil-cookie-consent/schongeil-cookie-consent.php' );
define( 'SGCC_CONSENT_COOKIE', 'sgcc_consent' );
define( 'SGCC_DB_VERSION', '1.1' );

/* ===================================================================
   WordPress Function Stubs
   =================================================================== */

// Options – In-Memory-Store fuer Tests.
$GLOBALS['sgcc_test_options'] = array();

function get_option( $key, $default = false ) {
    return array_key_exists( $key, $GLOBALS['sgcc_test_options'] ) ? $GLOBALS['sgcc_test_options'][ $key ] : $default;
}

function update_option( $key, $value, $autoload = null ) {
    $GLOBALS['sgcc_test_options'][ $key ] = $value;
    return true;
}

function add_option( $key, $value ) {
    if ( ! array_key_exists( $key, $GLOBALS['sgcc_test_options'] ) ) {
        $GLOBALS['sgcc_test_options'][ $key ] = $value;
    }
    return true;
}

// Hooks (no-op).
function add_filter( $tag, $function, $priority = 10, $accepted_args = 1 ) {}
function add_action( $tag, $function, $priority = 10, $accepted_args = 1 ) {}
function has_action( $tag, $function = false ) { return false; }
function do_action( $tag, ...$args ) {}

// Escaping / Sanitization.
function esc_attr( $text )  { return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' ); }
function esc_html( $text )  { return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' ); }
function esc_url( $url )    { return filter_var( $url, FILTER_SANITIZE_URL ) ?: ''; }
function sanitize_text_field( $str ) { return trim( strip_tags( (string) $str ) ); }
function sanitize_textarea_field( $str ) { return trim( strip_tags( (string) $str ) ); }
function sanitize_key( $key ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $key ) ); }
function absint( $val ) { return abs( intval( $val ) ); }
function wp_json_encode( $data ) { return json_encode( $data ); }
function sanitize_url( $url ) { return filter_var( $url, FILTER_SANITIZE_URL ); }

// URL-Parsing.
function wp_parse_url( $url, $component = -1 ) {
    if ( $component === -1 ) {
        return parse_url( $url );
    }
    return parse_url( $url, $component );
}

// Array helpers.
function wp_parse_args( $args, $defaults = array() ) {
    if ( is_string( $args ) ) {
        parse_str( $args, $args );
    }
    return array_merge( $defaults, (array) $args );
}

// Misc WP functions.
function is_admin() { return false; }
function home_url( $path = '' ) { return 'https://example.com' . $path; }
function get_permalink( $post_id = 0 ) { return 'https://example.com/?p=' . $post_id; }
function current_time( $type ) { return date( 'Y-m-d H:i:s' ); }
function wp_cache_flush() {}
function load_plugin_textdomain( $domain, $deprecated, $path ) {}

function wp_upload_dir() {
    return array(
        'basedir' => '/tmp/sgcc-test-uploads',
        'baseurl' => 'https://example.com/wp-content/uploads',
    );
}

function wp_mkdir_p( $path ) { return true; }

// HTTP (disabled in tests).
class WP_Error {
    public $code;
    public $message;
    public function __construct( $code = '', $message = '', $data = '' ) {
        $this->code    = $code;
        $this->message = $message;
    }
    public function get_error_message() { return $this->message; }
}

function is_wp_error( $thing ) { return $thing instanceof WP_Error; }
function wp_remote_get( $url, $args = array() ) { return new WP_Error( 'http_disabled', 'HTTP disabled in tests' ); }
function wp_remote_retrieve_response_code( $response ) { return 0; }
function wp_remote_retrieve_body( $response ) { return ''; }
function wp_remote_retrieve_header( $response, $header ) { return ''; }

/* ===================================================================
   Klassen laden
   =================================================================== */

require_once SGCC_PLUGIN_DIR . 'includes/class-sgcc-services.php';
require_once SGCC_PLUGIN_DIR . 'includes/class-sgcc-blocker.php';
require_once SGCC_PLUGIN_DIR . 'includes/class-sgcc-consent-log.php';
// class-sgcc-core.php wird NICHT geladen – bootstrappt das gesamte Plugin.
// Hex-Color-Regex wird isoliert in test-core.php getestet.
