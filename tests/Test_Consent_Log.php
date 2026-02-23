<?php
/**
 * Tests fuer SGCC_Consent_Log – IP-Anonymisierung.
 *
 * @package SchonGeil_Cookie_Consent\Tests
 */

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class Test_Consent_Log extends TestCase {

    private SGCC_Consent_Log $log;
    private ReflectionMethod $anonymize;

    protected function setUp(): void {
        $GLOBALS['sgcc_test_options'] = array();

        // Singleton zuruecksetzen.
        $ref = new ReflectionProperty( SGCC_Consent_Log::class, 'instance' );
        $ref->setValue( null, null );

        $this->log = SGCC_Consent_Log::get_instance();

        $this->anonymize = new ReflectionMethod( SGCC_Consent_Log::class, 'anonymize_ip' );
    }

    /* ==================================================================
       IPv4-Anonymisierung – letztes Oktett wird auf 0 gesetzt
       ================================================================== */

    public static function ipv4Provider(): array {
        return array(
            'standard'      => array( '192.168.1.42', '192.168.1.0' ),
            'already zero'  => array( '10.0.0.0', '10.0.0.0' ),
            'max values'    => array( '255.255.255.255', '255.255.255.0' ),
            'localhost'     => array( '127.0.0.1', '127.0.0.0' ),
            'class a'       => array( '8.8.8.8', '8.8.8.0' ),
            'class b'       => array( '172.16.254.99', '172.16.254.0' ),
        );
    }

    #[DataProvider('ipv4Provider')]
    public function test_anonymize_ipv4( string $ip, string $expected ): void {
        $this->assertSame( $expected, $this->anonymize->invoke( $this->log, $ip ) );
    }

    /* ==================================================================
       IPv6-Anonymisierung – letzte 80 Bit (5 Gruppen) maskiert
       ================================================================== */

    public static function ipv6Provider(): array {
        return array(
            'full ipv6' => array(
                '2001:0db8:85a3:0000:0000:8a2e:0370:7334',
                '2001:0db8:85a3:0:0:0:0:0',
            ),
            'shortened ipv6' => array(
                '2001:db8:85a3:0:0:8a2e:370:7334',
                '2001:db8:85a3:0:0:0:0:0',
            ),
            'loopback' => array(
                '0:0:0:0:0:0:0:1',
                '0:0:0:0:0:0:0:0',
            ),
        );
    }

    #[DataProvider('ipv6Provider')]
    public function test_anonymize_ipv6( string $ip, string $expected ): void {
        $this->assertSame( $expected, $this->anonymize->invoke( $this->log, $ip ) );
    }

    /* ==================================================================
       Edge Cases
       ================================================================== */

    public function test_anonymize_empty_string(): void {
        $this->assertSame( '0.0.0.0', $this->anonymize->invoke( $this->log, '' ) );
    }

    public function test_anonymize_null(): void {
        $this->assertSame( '0.0.0.0', $this->anonymize->invoke( $this->log, null ) );
    }

    public function test_anonymize_garbage_input(): void {
        $this->assertSame( '0.0.0.0', $this->anonymize->invoke( $this->log, 'not-an-ip' ) );
    }
}
